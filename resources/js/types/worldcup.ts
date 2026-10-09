export interface WorldCupTeam {
    id: string | null;
    name: string;
    flag?: string;
    fifaCode?: string;
    label?: string | null;
}

export interface WorldCupStadium {
    id: string;
    name: string;
    city: string;
    country: string;
}

export interface WorldCupMatch {
    id: string;
    homeTeam: WorldCupTeam;
    awayTeam: WorldCupTeam;
    homeScore: number;
    awayScore: number;
    homeScorers?: string | null;
    awayScorers?: string | null;
    stage: string;
    stageLabel: string;
    group: string;
    matchday: string;
    status: 'upcoming' | 'live' | 'finished';
    statusLabel: string;
    timeElapsed: string;
    localDate?: string | null;
    localDateFormatted?: string | null;
    isKnockout: boolean;
    stadium?: WorldCupStadium | null;
}

export interface WorldCupGroupStanding {
    teamId: string;
    name: string;
    flag: string;
    fifaCode: string;
    pts: number;
    gf: number;
    ga: number;
    gd: number;
}

export interface WorldCupGroup {
    group: string;
    standings: WorldCupGroupStanding[];
}

export interface WorldCupSettings {
    enabled: boolean;
    show_on_landing: boolean;
    preview_count: number;
    section_title: string;
}

export interface WorldCupApiStatus {
    token_configured: boolean;
    healthy: boolean;
    health?: Record<string, unknown> | null;
    games_count: number;
    cache_ttl_seconds: number;
    cache_ttl_static_seconds: number;
}

export interface WorldCupPreview {
    matches: WorldCupMatch[];
    liveMatches: WorldCupMatch[];
}
