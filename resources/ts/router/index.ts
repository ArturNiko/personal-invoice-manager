import { createRouter, createWebHistory } from 'vue-router';


export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/login',
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/Pages/Auth/LoginPage.vue'),
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/Pages/Auth/RegisterPage.vue'),
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: () => import('@/Pages/Auth/ForgotPasswordPage.vue'),
        },
        {
            path: '/reset-password/:token',
            name: 'reset-password',
            component: () => import('@/Pages/Auth/ResetPasswordPage.vue'),
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: () => import('@/Pages/Auth/VerifyEmailPage.vue'),
        },
        {
            path: '/invoice/create',
            name: 'invoice-create',
            component: () => import('@/Pages/InvoiceCreateView.vue'),
        },
        {
            path: '/invoices/:id/edit',
            name: 'invoice-edit',
            component: () => import('@/Pages/InvoiceEditView.vue'),
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
        {
            path: '/profile',
            name: 'profile',
            component: () => import('@/Pages/ProfilePage.vue'),
        },
    ],
});

const isGuestRoute = (path: string) =>
    path === '/login' ||
    path === '/register' ||
    path === '/forgot-password' ||
    path.startsWith('/reset-password');

router.beforeEach((to, _, next) => {
    const isLoggedIn = document.body.dataset.authenticated === '1';

    if (isLoggedIn && isGuestRoute(to.path)) {
        next('/calendar');
        return;
    }

    if (!isLoggedIn && !isGuestRoute(to.path)) {
        next('/login');
        return;
    }

    next();
})
