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
]);

export function shouldUsePersistentShell(pageName: string): boolean {
    if (pageName.startsWith('errors/')) {
        return false;
    }

    return !pagesWithoutShell.has(pageName);
}
