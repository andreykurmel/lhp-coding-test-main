<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface EventPayload {
    name: string;
    category?: string;
    description?: string;
    images?: string[];
    schedule?: {
        starts_at?: string | number;
        ends_at?: string | number;
    };
    [key: string]: any;
}

interface EventRow {
    id: string;
    type: string;
    status: string;
    created_time: number | null;
    starts_at: number | null;
    ends_at: number | null;
    latitude: number | null;
    longitude: number | null;
    country: string | null;
    city: string | null;
    address: string | null;
    payload: EventPayload;
    user: { id: number; name: string } | null;
}

const props = defineProps<{
    filters: { status: string | null; from: string | null; to: string | null; location: string | null };
    statuses: string[];
}>();

const form = reactive({
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    location: props.filters.location ?? '',
});

const rows = ref<EventRow[]>([]);
const page = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loadedBytes = ref(0);
const loadedMs = ref(0);
const loading = ref(false);
const hasLoadedOnce = ref(false);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const hasMore = computed(() => lastPage.value === null || page.value < lastPage.value);

const loadedSize = computed(() => {
    const kb = loadedBytes.value / 1024;
    return kb < 1024 ? `${kb.toFixed(1)} KB` : `${(kb / 1024).toFixed(2)} MB`;
});

const loadedSeconds = computed(() => (loadedMs.value / 1000).toFixed(1));

async function loadMore() {
    if (loading.value || !hasMore.value) {
        return;
    }
    loading.value = true;

    const params = new URLSearchParams({ page: String(page.value + 1) });
    if (form.status) params.set('status', form.status);
    if (form.from) params.set('from', form.from);
    if (form.to) params.set('to', form.to);
    if (form.location) params.set('location', form.location);

    try {
        const response = await fetch(`/events/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        rows.value.push(...payload.data);
        page.value = payload.current_page;
        lastPage.value = payload.last_page;
        total.value = payload.total;
        loadedBytes.value += payload.stats.bytes;
        loadedMs.value += payload.stats.ms;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    rows.value = [];
    page.value = 0;
    lastPage.value = null;
    total.value = null;
    loadedBytes.value = 0;
    loadedMs.value = 0;
    hasLoadedOnce.value = false;
    loadMore();
}

// Calendar Logic
const currentDate = ref(form.from ? new Date(form.from) : new Date());
const selectedDateKey = ref<string | null>(null);

const year = computed(() => currentDate.value.getFullYear());
const month = computed(() => currentDate.value.getMonth());
const monthLabel = computed(() => currentDate.value.toLocaleString(undefined, { month: 'long', year: 'numeric' }));
const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const calendarCells = computed(() => {
    const yearVal = year.value;
    const monthVal = month.value;
    
    const firstDayIndex = new Date(yearVal, monthVal, 1).getDay();
    const totalDays = new Date(yearVal, monthVal + 1, 0).getDate();
    const prevTotalDays = new Date(yearVal, monthVal, 0).getDate();
    
    const cells: { date: Date; isCurrentMonth: boolean; key: string; label: number }[] = [];
    
    // Prev month padding
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        const d = prevTotalDays - i;
        const date = new Date(yearVal, monthVal - 1, d);
        cells.push({
            date,
            isCurrentMonth: false,
            key: formatDateKey(date),
            label: d,
        });
    }
    
    // Current month days
    for (let d = 1; d <= totalDays; d++) {
        const date = new Date(yearVal, monthVal, d);
        cells.push({
            date,
            isCurrentMonth: true,
            key: formatDateKey(date),
            label: d,
        });
    }
    
    // Next month padding
    const remaining = 42 - cells.length;
    for (let d = 1; d <= remaining; d++) {
        const date = new Date(yearVal, monthVal + 1, d);
        cells.push({
            date,
            isCurrentMonth: false,
            key: formatDateKey(date),
            label: d,
        });
    }
    
    return cells;
});

const eventsByDate = computed(() => {
    const map: Record<string, EventRow[]> = {};
    for (const event of rows.value) {
        const timestamp = event.payload?.schedule?.starts_at || event.starts_at;
        if (timestamp) {
            const date = new Date(Number(timestamp) * 1000);
            const key = formatDateKey(date);
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(event);
        }
    }
    return map;
});

const filteredRows = computed(() => {
    if (!selectedDateKey.value) {
        return rows.value;
    }
    return rows.value.filter((event) => {
        const timestamp = event.payload?.schedule?.starts_at || event.starts_at;
        if (!timestamp) return false;
        const date = new Date(Number(timestamp) * 1000);
        return formatDateKey(date) === selectedDateKey.value;
    });
});

const agendaHeader = computed(() => {
    if (selectedDateKey.value) {
        return `Events on ${formatSelectedDate(selectedDateKey.value)}`;
    }
    return `Events in ${monthLabel.value}`;
});

function formatDateKey(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function isToday(date: Date): boolean {
    const today = new Date();
    return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
    );
}

function selectDay(cell: { date: Date; isCurrentMonth: boolean; key: string; label: number }) {
    if (!cell.isCurrentMonth) {
        currentDate.value = cell.date;
        updateMonthFilters();
        selectedDateKey.value = cell.key;
    } else {
        if (selectedDateKey.value === cell.key) {
            selectedDateKey.value = null;
        } else {
            selectedDateKey.value = cell.key;
        }
    }
}

function prevMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
    updateMonthFilters();
}

function nextMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
    updateMonthFilters();
}

function updateMonthFilters() {
    const yearVal = currentDate.value.getFullYear();
    const monthVal = currentDate.value.getMonth();
    
    const firstDay = `${yearVal}-${String(monthVal + 1).padStart(2, '0')}-01`;
    const lastDate = new Date(yearVal, monthVal + 1, 0).getDate();
    const lastDay = `${yearVal}-${String(monthVal + 1).padStart(2, '0')}-${String(lastDate).padStart(2, '0')}`;
    
    form.from = firstDay;
    form.to = lastDay;
    
    selectedDateKey.value = null;
    applyFilters();
}

function onDateInputChange() {
    if (form.from) {
        currentDate.value = new Date(form.from);
    }
    selectedDateKey.value = null;
}

function formatSelectedDate(key: string): string {
    const date = new Date(key + 'T00:00:00');
    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Event Row Helpers
function eventName(event: EventRow): string {
    return event.payload?.name || 'Unnamed Event';
}

function firstImage(event: EventRow): string | undefined {
    const images = event.payload?.images;
    if (Array.isArray(images) && images.length > 0) {
        return images[0];
    }
    return undefined;
}

function eventLocation(event: EventRow): string {
    if (event.city && event.country) {
        return `${event.city}, ${event.country}`;
    }
    if (event.address) {
        return event.address;
    }
    if (event.latitude !== null && event.longitude !== null) {
        return `${event.latitude.toFixed(4)}, ${event.longitude.toFixed(4)}`;
    }
    return 'Remote / Online';
}

function formatStartsAt(event: EventRow): string {
    const timestamp = event.payload?.schedule?.starts_at || event.starts_at;
    if (!timestamp) return '—';
    const date = new Date(Number(timestamp) * 1000);
    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
};

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '400px' },
    );
    if (sentinel.value) {
        observer.observe(sentinel.value);
    }
    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events Visual 2" />

    <div class="flex flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Events Visual 2 (Calendar View)</h1>
            <p class="text-sm text-muted-foreground">
                {{ total !== null ? `${total.toLocaleString()} total events` : '—' }}
            </p>
        </div>

        <!-- Filters Form -->
        <form class="flex flex-wrap items-end gap-3" @submit.prevent>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="status">Status</label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">All</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="from">From</label>
                <input
                    id="from"
                    v-model="form.from"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="onDateInputChange"
                />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="to">To</label>
                <input
                    id="to"
                    v-model="form.to"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="onDateInputChange"
                />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="location">Location</label>
                <input
                    id="location"
                    v-model="form.location"
                    type="text"
                    placeholder="Search by city, country..."
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm w-48"
                />
            </div>
            <Button type="button" @click.prevent="applyFilters">Filter</Button>
        </form>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 mt-2">
            <!-- Calendar Card -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm p-4">
                    <!-- Month Selector Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold">{{ monthLabel }}</h2>
                        <div class="flex gap-1">
                            <Button variant="outline" size="icon" class="h-7 w-7" @click="prevMonth">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Button>
                            <Button variant="outline" size="icon" class="h-7 w-7" @click="nextMonth">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Button>
                        </div>
                    </div>

                    <!-- Weekdays -->
                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-muted-foreground mb-1">
                        <div v-for="day in weekdays" :key="day" class="py-1">{{ day.slice(0, 2) }}</div>
                    </div>

                    <!-- Days Grid -->
                    <div class="grid grid-cols-7 gap-1 animate-in fade-in duration-300">
                        <button
                            v-for="cell in calendarCells"
                            :key="cell.key"
                            type="button"
                            :class="[
                                'relative flex aspect-square flex-col items-center justify-center rounded-lg text-xs font-medium transition-all focus:outline-hidden cursor-pointer select-none',
                                cell.isCurrentMonth ? 'text-foreground' : 'text-muted-foreground/30',
                                selectedDateKey === cell.key
                                    ? 'bg-primary text-primary-foreground font-semibold shadow-sm hover:bg-primary/95'
                                    : 'hover:bg-muted/80',
                                isToday(cell.date) && selectedDateKey !== cell.key ? 'border border-primary text-primary' : ''
                            ]"
                            @click="selectDay(cell)"
                        >
                            <span>{{ cell.label }}</span>
                            <!-- Event Indicator Dot -->
                            <span
                                v-if="eventsByDate[cell.key]?.length > 0"
                                :class="[
                                    'absolute bottom-1 h-1 w-1 rounded-full',
                                    selectedDateKey === cell.key ? 'bg-primary-foreground' : 'bg-primary'
                                ]"
                            ></span>
                        </button>
                    </div>

                    <!-- Selected Info / Reset Button -->
                    <div class="mt-4 pt-4 border-t flex items-center justify-between text-xs text-muted-foreground">
                        <span v-if="selectedDateKey" class="animate-in slide-in-from-left-1 duration-200">
                            Selected: {{ formatSelectedDate(selectedDateKey) }}
                        </span>
                        <span v-else>Showing all events for month</span>
                        <button
                            v-if="selectedDateKey"
                            type="button"
                            class="text-primary font-medium hover:underline focus:outline-hidden cursor-pointer"
                            @click="selectedDateKey = null"
                        >
                            Clear Day
                        </button>
                    </div>
                </div>
            </div>

            <!-- Agenda List -->
            <div class="lg:col-span-8 flex flex-col gap-4">
                <div class="flex items-center justify-between border-b pb-2">
                    <h2 class="text-sm font-semibold text-foreground">
                        {{ agendaHeader }}
                    </h2>
                    <span class="text-xs text-muted-foreground">
                        Showing {{ filteredRows.length }} loaded events
                    </span>
                </div>

                <div v-if="!loading && hasLoadedOnce && filteredRows.length === 0" class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground border rounded-lg bg-card/50">
                    <svg class="h-12 w-12 text-muted-foreground/50 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-foreground mb-1">No events scheduled</h3>
                    <p class="text-sm">There are no events on this day. Try another day or adjust filters.</p>
                </div>

                <div v-else class="flex flex-col gap-3">
                    <Link
                        v-for="event in filteredRows"
                        :key="event.id"
                        :href="`/events/${event.id}`"
                        class="group flex flex-col sm:flex-row overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm transition-all duration-300 hover:shadow-md hover:border-primary/30"
                    >
                        <!-- Image Section -->
                        <div class="relative aspect-video sm:aspect-square w-full sm:w-28 overflow-hidden bg-muted shrink-0">
                            <img
                                v-if="firstImage(event)"
                                :src="firstImage(event)"
                                :alt="eventName(event)"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/10 to-accent/10 text-muted-foreground">
                                <svg class="h-6 w-6 stroke-1.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Info Section -->
                        <div class="flex-1 p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <Badge :variant="statusVariant(event.status)" class="capitalize text-[10px] py-0 px-2">
                                        {{ event.status.replace('_', ' ') }}
                                    </Badge>
                                    <Badge variant="outline" class="capitalize text-[10px] py-0 px-2">
                                        {{ event.type }}
                                    </Badge>
                                </div>
                                <h3 class="mb-1 text-sm font-semibold leading-tight group-hover:text-primary transition-colors line-clamp-1">
                                    {{ eventName(event) }}
                                </h3>
                                <div class="text-xs text-primary font-medium flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ formatStartsAt(event) }}</span>
                                </div>
                            </div>

                            <div class="mt-2 pt-2 border-t flex items-center justify-between text-xs text-muted-foreground">
                                <div class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="line-clamp-1">{{ eventLocation(event) }}</span>
                                </div>
                                <span class="text-primary hover:underline text-[10px] font-medium">View Details</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <div ref="sentinel"></div>

        <div class="py-2 text-sm text-gray-400">
            <span v-if="loading">loading...</span>
            <span v-else-if="hasLoadedOnce">Loaded {{ loadedSize }} in {{ loadedSeconds }}s</span>
        </div>
    </div>
</template>
