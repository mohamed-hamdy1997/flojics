<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
});

const tickets = ref(props.tickets.data.map((ticket) => ({ ...ticket })));
const escalatingId = ref(null);
const errors = ref({});

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function priorityClass(priority) {
    return {
        Low: 'bg-gray-100 text-gray-700',
        Medium: 'bg-blue-100 text-blue-700',
        High: 'bg-amber-100 text-amber-700',
        Urgent: 'bg-red-100 text-red-700',
    }[priority] ?? 'bg-gray-100 text-gray-700';
}

function statusClass(status) {
    return {
        Open: 'bg-gray-100 text-gray-700',
        'In Progress': 'bg-blue-100 text-blue-700',
        Escalated: 'bg-red-100 text-red-700',
        Resolved: 'bg-green-100 text-green-700',
        Closed: 'bg-gray-200 text-gray-500',
    }[status] ?? 'bg-gray-100 text-gray-700';
}

async function escalate(ticket) {
    escalatingId.value = ticket.id;
    errors.value = { ...errors.value, [ticket.id]: null };

    try {
        const { data } = await axios.post(`/api/tickets/${ticket.id}/escalate`);
        const index = tickets.value.findIndex((t) => t.id === ticket.id);
        if (index !== -1) {
            tickets.value[index] = data.ticket;
        }
    } catch (e) {
        errors.value = {
            ...errors.value,
            [ticket.id]: e.response?.data?.message ?? 'Failed to escalate ticket.',
        };
    } finally {
        escalatingId.value = null;
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="mx-auto max-w-5xl">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Tickets</h1>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Ticket ID</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Escalation Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="ticket in tickets" :key="ticket.id">
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ ticket.id }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ ticket.subject }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="priorityClass(ticket.priority)">
                                    {{ ticket.priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClass(ticket.status)">
                                    {{ ticket.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDate(ticket.escalated_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="ticket.status !== 'Escalated'"
                                    type="button"
                                    class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="escalatingId === ticket.id"
                                    @click="escalate(ticket)"
                                >
                                    {{ escalatingId === ticket.id ? 'Escalating…' : 'Escalate' }}
                                </button>
                                <p v-if="errors[ticket.id]" class="mt-1 text-xs text-red-600">{{ errors[ticket.id] }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="props.tickets.meta.links.length > 3" class="mt-4 flex flex-wrap items-center gap-1">
                <template v-for="(link, index) in props.tickets.meta.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="rounded-md px-3 py-1.5 text-sm text-gray-400"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        class="rounded-md px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
