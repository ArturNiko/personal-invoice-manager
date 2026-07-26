import { createRouter, createWebHistory } from 'vue-router'

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/calendar',
        },
        {
            path: '/create',
            name: 'create',
            component: () => import('@/Pages/InvoiceCreateView.vue'),
        },
        {
            path: '/calendar',
            name: 'calendar',
            component: () => import('@/Pages/CalendarView.vue'),
        },
        {
            path: '/list',
            name: 'list',
            component: () => import('@/Pages/InvoiceListView.vue'),
        },
    ],
})
