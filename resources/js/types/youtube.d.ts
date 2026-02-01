/**
 * Type declarations for YouTube IFrame API
 *
 * Minimal declarations for the YouTube Player API used in YouTubePlayer.vue
 */

declare namespace YT {
    export interface Player {
        playVideo(): void;
        pauseVideo(): void;
        seekTo(seconds: number, allowSeekAhead: boolean): void;
        getCurrentTime(): number;
        getDuration(): number;
        getPlayerState(): number;
        loadVideoById(videoId: string | { videoId: string; startSeconds?: number }): void;
        destroy(): void;
    }

    export interface PlayerOptions {
        videoId: string;
        playerVars?: {
            autoplay?: 0 | 1;
            controls?: 0 | 1;
            modestbranding?: 0 | 1;
            rel?: 0 | 1;
            [key: string]: unknown;
        };
        events?: {
            onReady?: (event: PlayerEvent) => void;
            onStateChange?: (event: OnStateChangeEvent) => void;
            onError?: (event: PlayerEvent) => void;
            [key: string]: unknown;
        };
    }

    export interface PlayerEvent {
        target: Player;
    }

    export interface OnStateChangeEvent extends PlayerEvent {
        data: number;
    }

    export enum PlayerState {
        UNSTARTED = -1,
        ENDED = 0,
        PLAYING = 1,
        PAUSED = 2,
        BUFFERING = 3,
        CUED = 5,
    }
}

interface Window {
    YT?: {
        Player: new (elementId: string, options: YT.PlayerOptions) => YT.Player;
        PlayerState: typeof YT.PlayerState;
    };
    onYouTubeIframeAPIReady?: () => void;
}
