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
    filters: { status: string | null; from: string };
    statuses: string[];
}>();

const form = reactive({
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
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
    <Head title="Events Visual 1" />

    <div class="flex flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Events Visual 1 (Grid View)</h1>
            <p class="text-sm text-muted-foreground">
                {{ total !== null ? `${total.toLocaleString()} total events` : '—' }}
            </p>
        </div>

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
                />
            </div>
            <Button type="button" @click.prevent="applyFilters">Filter</Button>
        </form>

        <div v-if="!loading && hasLoadedOnce && rows.length === 0" class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground border rounded-lg bg-card/50">
            <svg class="h-12 w-12 text-muted-foreground/50 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium text-foreground mb-1">No events found</h3>
            <p class="text-sm">Try adjusting your filters to find events.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <Link
                v-for="event in rows"
                :key="event.id"
                :href="`/events/${event.id}`"
                class="group flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
            >
                <!-- Image Section -->
                <div class="relative aspect-video w-full overflow-hidden bg-muted">
                    <img
                        v-if="firstImage(event)"
                        :src="firstImage(event)"
                        :alt="eventName(event)"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/10 to-accent/10 text-muted-foreground">
                        <svg class="h-8 w-8 stroke-1.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>

                    <!-- Badges overlay -->
                    <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                        <Badge :variant="statusVariant(event.status)" class="capitalize shadow-sm">
                            {{ event.status.replace('_', ' ') }}
                        </Badge>
                    </div>
                    <div class="absolute bottom-2 right-2">
                        <Badge variant="outline" class="bg-background/80 backdrop-blur-xs capitalize shadow-sm text-[10px] py-0.5 px-2">
                            {{ event.type }}
                        </Badge>
                    </div>
                </div>

                <!-- Info Section -->
                <div class="flex flex-1 flex-col p-4">
                    <!-- Date -->
                    <div class="mb-2 flex items-center gap-1.5 text-xs font-medium text-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ formatStartsAt(event) }}</span>
                    </div>

                    <!-- Title -->
                    <h3 class="mb-3 line-clamp-2 text-sm font-semibold leading-tight group-hover:text-primary transition-colors">
                        {{ eventName(event) }}
                    </h3>

                    <!-- Spacer -->
                    <div class="mt-auto pt-3 border-t">
                        <!-- Location -->
                        <div class="flex items-start gap-1.5 text-xs text-muted-foreground">
                            <svg class="h-3.5 w-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="line-clamp-1" :title="eventLocation(event)">{{ eventLocation(event) }}</span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <div ref="sentinel"></div>

        <div class="py-2 text-sm text-gray-400">
            <span v-if="loading">loading...</span>
            <span v-else-if="hasLoadedOnce">Loaded {{ loadedSize }} in {{ loadedSeconds }}s</span>
        </div>
    </div>
</template>
