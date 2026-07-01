const pagesWithoutShell = new Set(['Welcome', 'auth/Login', 'auth/Register', 'auth/ForgotPassword', 'auth/ResetPassword', 'auth/VerifyEmail', 'auth/ConfirmPassword', 'auth/VerifyEmailOtp', 'registration/Register', 'registration/Success']);

export function shouldUsePersistentShell(pageName: string): boolean {
    return !pagesWithoutShell.has(pageName);
}
