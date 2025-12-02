import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';

type SaveOptions = {
    url: string;
    redirectUrl?: string | null;
    mode: 'create' | 'edit';
    id?: number | string;
    successMessage?: string;
    errorMessage?: string;
    onSuccess?: (response?: any) => void;
    onError?: (errors: Record<string, string>) => void;
};

export function useHandleFormSave() {
    const { toast } = useToast();

    /**
     * Mengembalikan error field ke pemanggil, hanya error umum yang pakai toast
     */
    const handleError = (errors: Record<string, any>, fallbackMessage: string, setFormErrors?: (errors: Record<string, string>) => void) => {
        if (!errors || Object.keys(errors).length === 0) {
            toast({ title: fallbackMessage, variant: 'destructive' });
            return;
        }
        // Jika ada setFormErrors, lempar error ke form
        if (setFormErrors) {
            setFormErrors(errors);
        } else {
            // Fallback: tetap pakai toast jika tidak ada handler
            Object.entries(errors).forEach(([field, message]) => {
                toast({
                    title: `${field}: ${message}`,
                    variant: 'destructive',
                });
            });
        }
    };

    const save = (data: Record<string, any>, options: SaveOptions & { setFormErrors?: (errors: Record<string, string>) => void }) => {
        const {
            url,
            redirectUrl = url,
            mode,
            id,
            errorMessage = 'Gagal menyimpan data',
            onSuccess,
            setFormErrors,
            onError,
        } = options;

        console.log('Form data being sent:', data);
        console.log('Has file:', !!data.file);
        if (data.file) {
            console.log('File details:', {
                name: data.file.name,
                size: data.file.size,
                type: data.file.type,
            });
        }

        const hasFiles = Object.values(data).some((value) => value instanceof File);
        let requestData: FormData | Record<string, any> = data;

        if (hasFiles) {
            const formData = new FormData();
            Object.entries(data).forEach(([key, value]) => {
                if (value instanceof File) {
                    formData.append(key, value);
                } else if (Array.isArray(value)) {
                    value.forEach((item) => formData.append(`${key}[]`, item));
                } else {
                    if (value === null || value === undefined) {
                        formData.append(key, '');
                    } else {
                        formData.append(key, value);
                    }
                }
            });
            requestData = formData;
            console.log('Using FormData for file upload');
        }

        console.log('RequestData (final):', requestData);
        if (requestData instanceof FormData) {
            for (const pair of requestData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }

        // For edit operations, we'll use POST with _method=PUT to handle file uploads properly
        if (mode === 'edit' && id) {
            if (requestData instanceof FormData) {
                requestData.append('_method', 'PUT');
            } else {
                requestData = { ...requestData, _method: 'PUT' };
            }

            // Append the ID to the URL for edit operations
            return router.post(`${url}/${id}`, requestData, {
                ...(requestData instanceof FormData ? { forceFormData: true } : {}),
                onSuccess: (response: any) => {
                    // Jangan tampilkan toast jika sudah ada flash message dari backend
                    // Flash message akan ditampilkan oleh AppSidebarLayout
                    // toast({ title: successMessage, variant: 'success' });
                    if (onSuccess) {
                        onSuccess(response);
                    }
                    if (redirectUrl) {
                        router.visit(redirectUrl);
                    }
                },
                onError: (errors) => {
                    if (onError) {
                        onError(errors);
                    } else {
                        handleError(errors, errorMessage, setFormErrors);
                    }
                },
            });
        }

        // For create operations, also add redirectUrl
        return router.post(url, requestData, {
            ...(requestData instanceof FormData ? { forceFormData: true } : {}),
            onSuccess: (response: any) => {
                // Jangan tampilkan toast jika sudah ada flash message dari backend
                // Flash message akan ditampilkan oleh AppSidebarLayout
                // toast({ title: successMessage, variant: 'success' });
                if (onSuccess) {
                    onSuccess(response);
                }
                if (redirectUrl) {
                    router.visit(redirectUrl);
                }
            },
            onError: (errors) => {
                if (onError) {
                    onError(errors);
                } else {
                    handleError(errors, errorMessage, setFormErrors);
                }
            },
        });
    };

    return { save };
}
