import { SEO_APP_NAME, SEO_HOME_DESCRIPTION, absoluteUrl } from '@/lib/seo';

export type SchemaObject = Record<string, unknown>;

export function buildOrganizationSchema(baseUrl: string): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'GovernmentOrganization',
        name: 'Dinas Kepemudaan dan Olahraga Kabupaten Bogor',
        alternateName: SEO_APP_NAME,
        url: baseUrl,
        logo: absoluteUrl('/web-app-manifest-512x512.png', baseUrl),
        email: 'dispora@bogorkab.go.id',
        address: {
            '@type': 'PostalAddress',
            addressLocality: 'Kabupaten Bogor',
            addressRegion: 'Jawa Barat',
            addressCountry: 'ID',
        },
    };
}

export function buildWebSiteSchema(baseUrl: string): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: SEO_APP_NAME,
        url: baseUrl,
        description: SEO_HOME_DESCRIPTION,
        inLanguage: 'id-ID',
        publisher: {
            '@type': 'GovernmentOrganization',
            name: 'Dinas Kepemudaan dan Olahraga Kabupaten Bogor',
        },
    };
}

export function buildWebPageSchema(options: {
    baseUrl: string;
    name: string;
    description: string;
    url: string;
}): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: options.name,
        description: options.description,
        url: options.url,
        inLanguage: 'id-ID',
        isPartOf: {
            '@type': 'WebSite',
            name: SEO_APP_NAME,
            url: options.baseUrl,
        },
    };
}

export function buildBreadcrumbSchema(items: Array<{ name: string; url: string }>): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: item.name,
            item: item.url,
        })),
    };
}

export function buildArticleSchema(options: {
    baseUrl: string;
    headline: string;
    description: string;
    url: string;
    dateModified?: string;
}): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: options.headline,
        description: options.description,
        url: options.url,
        inLanguage: 'id-ID',
        dateModified: options.dateModified,
        author: {
            '@type': 'Organization',
            name: 'Dinas Kepemudaan dan Olahraga Kabupaten Bogor',
        },
        publisher: {
            '@type': 'Organization',
            name: SEO_APP_NAME,
            logo: {
                '@type': 'ImageObject',
                url: absoluteUrl('/web-app-manifest-512x512.png', options.baseUrl),
            },
        },
        mainEntityOfPage: options.url,
    };
}

export function buildEventSchema(options: {
    baseUrl: string;
    name: string;
    description: string;
    url: string;
    image?: string | null;
    startDate?: string | null;
    endDate?: string | null;
    location?: string | null;
    status?: string;
}): SchemaObject {
    const eventStatus = options.status === 'selesai' ? 'https://schema.org/EventScheduled' : 'https://schema.org/EventScheduled';

    return {
        '@context': 'https://schema.org',
        '@type': 'Event',
        name: options.name,
        description: options.description,
        url: options.url,
        image: options.image ? absoluteUrl(options.image, options.baseUrl) : absoluteUrl('/web-app-manifest-512x512.png', options.baseUrl),
        startDate: options.startDate ?? undefined,
        endDate: options.endDate ?? undefined,
        eventStatus,
        eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
        location: options.location
            ? {
                  '@type': 'Place',
                  name: options.location,
                  address: {
                      '@type': 'PostalAddress',
                      addressLocality: options.location,
                      addressCountry: 'ID',
                  },
              }
            : undefined,
        organizer: {
            '@type': 'GovernmentOrganization',
            name: 'Dinas Kepemudaan dan Olahraga Kabupaten Bogor',
            url: options.baseUrl,
        },
    };
}

export function buildFaqSchema(items: { question: string; answer: string }[]): SchemaObject {
    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: items.map((item) => ({
            '@type': 'Question',
            name: item.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.answer,
            },
        })),
    };
}

export function serializeSchema(schema: SchemaObject | SchemaObject[]): string {
    if (Array.isArray(schema)) {
        return JSON.stringify({
            '@context': 'https://schema.org',
            '@graph': schema.map((item) => {
                const { '@context': _context, ...rest } = item;

                return rest;
            }),
        });
    }

    return JSON.stringify(schema);
}
