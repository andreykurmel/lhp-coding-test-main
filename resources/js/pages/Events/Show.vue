<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';

interface Attendee {
    id: number;
    name: string;
    status: 'interested' | 'attending';
    created_at: string | null;
}

interface EventPayload {
    name?: string;
    category?: string;
    images?: string[];
    venue?: {
        name?: string;
        capacity?: number;
    };
    pricing?: {
        currency?: string;
        min_price?: number;
    };
}

interface EventDetail {
    id: string;
    type: string;
    status: string;
    starts_at: number | null;
    ends_at: number | null;
    country: string | null;
    city: string | null;
    address: string | null;
    payload: EventPayload;
    attendees: Attendee[];
    user: { id: number; name: string } | null;
}

interface PageProps extends Record<string, unknown> {
    auth?: {
        user?: {
            name: string;
            email: string;
        } | null;
    };
}

const props = defineProps<{ event: EventDetail }>();

const page = usePage<PageProps>();
const { getInitials } = useInitials();

const eventName = computed(
    () => props.event.payload.name ?? `${props.event.type} event`,
);
const eventImage = computed(
    () => props.event.payload.images?.[0] ?? '/storage/images/event1.jpeg',
);
const venueName = computed(
    () => props.event.payload.venue?.name ?? 'Venue to be announced',
);
const attendeeCounts = computed(() => ({
    attending: props.event.attendees.filter(
        (attendee) => attendee.status === 'attending',
    ).length,
    interested: props.event.attendees.filter(
        (attendee) => attendee.status === 'interested',
    ).length,
}));

const startsAt = computed(() => formatDateTime(props.event.starts_at));
const endsAt = computed(() => formatDateTime(props.event.ends_at));
const price = computed(() => {
    const amount = props.event.payload.pricing?.min_price;

    if (amount === undefined || amount === null) {
        return 'Free or TBA';
    }

    const currency = props.event.payload.pricing?.currency ?? 'USD';

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
});

const currentUser = computed(() => page.props.auth?.user ?? null);

const form = useForm({
    name: currentUser.value?.name ?? '',
    email: currentUser.value?.email ?? '',
    status: 'attending',
});

function formatDateTime(timestamp: number | null): string {
    if (!timestamp) {
        return 'TBA';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(timestamp * 1000));
}

function register(): void {
    form.post(`/events/${props.event.id}/register`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="eventName" />

    <div class="min-h-screen bg-background">
        <section
            class="relative min-h-[420px] overflow-hidden border-b bg-muted"
        >
            <img
                :src="eventImage"
                :alt="eventName"
                class="absolute inset-0 size-full object-cover"
            />
            <div class="absolute inset-0 bg-black/55" />

            <div
                class="relative mx-auto flex min-h-[420px] max-w-7xl flex-col justify-between px-4 py-6 text-white sm:px-6 lg:px-8"
            >
                <Link
                    href="/events"
                    class="inline-flex w-fit items-center gap-2 text-sm font-medium text-white/85 hover:text-white"
                >
                    <ArrowLeftIcon class="size-4" />
                    Back to events
                </Link>

                <div class="max-w-4xl pb-4">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <Badge
                            variant="secondary"
                            class="bg-white/90 text-foreground"
                            >{{ event.status }}</Badge
                        >
                        <Badge
                            variant="outline"
                            class="border-white/60 bg-black/20 text-white"
                            >{{ event.payload.category ?? event.type }}</Badge
                        >
                    </div>
                    <h1
                        class="max-w-3xl text-4xl leading-tight font-semibold sm:text-5xl"
                    >
                        {{ eventName }}
                    </h1>
                    <p
                        class="mt-4 max-w-2xl text-base text-white/80 sm:text-lg"
                    >
                        {{ venueName
                        }}<span v-if="event.city">, {{ event.city }}</span>
                    </p>
                </div>
            </div>
        </section>

        <main
            class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8"
        >
            <div class="space-y-6">
                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border bg-card p-4">
                        <CalendarDaysIcon class="mb-3 size-5 text-primary" />
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Starts
                        </p>
                        <p class="mt-1 text-sm font-medium">{{ startsAt }}</p>
                    </div>
                    <div class="rounded-lg border bg-card p-4">
                        <ClockIcon class="mb-3 size-5 text-primary" />
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Ends
                        </p>
                        <p class="mt-1 text-sm font-medium">{{ endsAt }}</p>
                    </div>
                    <div class="rounded-lg border bg-card p-4">
                        <MapPinIcon class="mb-3 size-5 text-primary" />
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Location
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{
                                event.address ??
                                event.city ??
                                event.country ??
                                'TBA'
                            }}
                        </p>
                    </div>
                    <div class="rounded-lg border bg-card p-4">
                        <UsersIcon class="mb-3 size-5 text-primary" />
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Interest
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{ event.attendees.length }} registered
                        </p>
                    </div>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Event Details</h2>
                            <p
                                class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                            >
                                Join this {{ event.type }} at {{ venueName }}.
                                Registration keeps you on the attendee list and
                                sends reminder emails before the event starts.
                            </p>
                        </div>
                        <div
                            class="rounded-md border px-3 py-2 text-sm font-medium"
                        >
                            {{ price }}
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Host</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.user?.name ?? 'Event team' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Capacity</dt>
                            <dd class="mt-1 font-medium">
                                {{
                                    event.payload.venue?.capacity?.toLocaleString() ??
                                    'TBA'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">City</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.city ?? 'TBA' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Country</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.country ?? 'TBA' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Attendees</h2>
                            <p class="text-sm text-muted-foreground">
                                {{ attendeeCounts.attending }} attending,
                                {{ attendeeCounts.interested }} interested
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="event.attendees.length"
                        class="mt-5 grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            v-for="attendee in event.attendees"
                            :key="attendee.id"
                            class="flex items-center gap-3 rounded-lg border p-3"
                        >
                            <Avatar class="size-10">
                                <AvatarFallback>{{
                                    getInitials(attendee.name)
                                }}</AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ attendee.name }}
                                </p>
                                <p
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    {{ attendee.status }}
                                </p>
                            </div>
                            <Badge
                                :variant="
                                    attendee.status === 'attending'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ attendee.status }}
                            </Badge>
                        </div>
                    </div>

                    <p
                        v-else
                        class="mt-5 rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        No attendees yet.
                    </p>
                </section>
            </div>

            <aside class="lg:sticky lg:top-6 lg:self-start">
                <form
                    class="rounded-lg border bg-card p-5 shadow-sm"
                    @submit.prevent="register"
                >
                    <h2 class="text-lg font-semibold">Register</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Save your spot and receive event reminders.
                    </p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="name" class="text-sm font-medium"
                                >Name</label
                            >
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            />
                            <InputError
                                :message="form.errors.name"
                                class="mt-1"
                            />
                        </div>

                        <div>
                            <label for="email" class="text-sm font-medium"
                                >Email</label
                            >
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            />
                            <InputError
                                :message="form.errors.email"
                                class="mt-1"
                            />
                        </div>

                        <div>
                            <p class="text-sm font-medium">Status</p>
                            <div
                                class="mt-2 grid grid-cols-2 rounded-lg border bg-muted p-1"
                            >
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-2 text-sm font-medium"
                                    :class="
                                        form.status === 'attending'
                                            ? 'bg-background shadow-sm'
                                            : 'text-muted-foreground'
                                    "
                                    @click="form.status = 'attending'"
                                >
                                    Attending
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-2 text-sm font-medium"
                                    :class="
                                        form.status === 'interested'
                                            ? 'bg-background shadow-sm'
                                            : 'text-muted-foreground'
                                    "
                                    @click="form.status = 'interested'"
                                >
                                    Interested
                                </button>
                            </div>
                            <InputError
                                :message="form.errors.status"
                                class="mt-1"
                            />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="mt-5 w-full"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing ? 'Saving...' : 'Register for event'
                        }}
                    </Button>
                </form>
            </aside>
        </main>
    </div>
</template>
