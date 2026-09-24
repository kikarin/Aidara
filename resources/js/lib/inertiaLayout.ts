const pagesWithoutShell = new Set([
    'Welcome',
    'auth/Login',
    'auth/Register',
    'auth/ForgotPassword',
    'auth/ResetPassword',
    'auth/VerifyEmail',
    'auth/ConfirmPassword',
    'auth/VerifyEmailOtp',
    'registration/Register',
    'registration/Success',
    'legal/Show',
    'worldcup/Index',
    'event/PublicIndex',
    'event/PublicShow',
    'modules/e-booking/Catalog',
    'modules/e-booking/Show',
    'modules/e-booking/Login',
    'modules/e-booking/Register',
    'modules/e-booking/History',
    'modules/e-booking/Detail',
    'modules/e-booking/admin/Login',
    'modules/e-booking/admin/Dashboard',
    'modules/e-booking/admin/Bookings',
    'modules/e-booking/admin/BookingShow',
    'modules/e-booking/admin/Settings',
]);

export function shouldUsePersistentShell(pageName: string): boolean {
    if (pageName.startsWith('errors/')) {
        return false;
    }

    if (pageName.startsWith('modules/e-booking/')) {
        return false;
    }

    return !pagesWithoutShell.has(pageName);
}
