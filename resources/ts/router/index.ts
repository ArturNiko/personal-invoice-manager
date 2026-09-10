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
            meta: { guest: true },
            component: () => import('@/Pages/Auth/LoginPage.vue'),
        },
        {
            path: '/register',
            name: 'register',
            meta: { guest: true },
            component: () => import('@/Pages/Auth/RegisterPage.vue'),
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            meta: { guest: true },
            component: () => import('@/Pages/Auth/ForgotPasswordPage.vue'),
        },
        {
            path: '/reset-password/:token',
            name: 'reset-password',
            meta: { guest: true },
            component: () => import('@/Pages/Auth/ResetPasswordPage.vue'),
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            meta: { unverified: true },
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
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('@/Pages/DashboardView.vue'),
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
        {
            path: '/:pathMatch(.*)*',
            name: 'fallback',
            component: () => import('@/Pages/NotFoundView.vue'),
        },
    ],
});


router.beforeEach((to, _, next) => {
    const isLoggedIn = document.body.dataset.authenticated === '1';

    const isGuestRoute = to.meta.guest === true;

    if (to.name === 'fallback') {
        next();
        return;
    }

    if (isLoggedIn && isGuestRoute) {
        next('/dashboard');
        return;
    }

    if (!isLoggedIn && !isGuestRoute) {
        next('/login');
        return;
    }

    next();
});
