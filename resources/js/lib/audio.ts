/**
 * Reads the length of a local audio file in the browser (milliseconds).
 * Returns null when the browser cannot read it. The value is only shown as
 * information; the server never relies on it.
 */
export function readAudioDuration(file: File): Promise<number | null> {
    return new Promise((resolve) => {
        const objectUrl = URL.createObjectURL(file);
        const audio = new Audio();

        const finish = (value: number | null) => {
            URL.revokeObjectURL(objectUrl);
            resolve(value);
        };

        audio.preload = 'metadata';
        audio.onloadedmetadata = () =>
            finish(
                Number.isFinite(audio.duration)
                    ? Math.round(audio.duration * 1000)
                    : null,
            );
        audio.onerror = () => finish(null);
        audio.src = objectUrl;
    });
}

export const AUDIO_ACCEPT = '.mp3,.wav,.ogg,.oga,.m4a,.aac,.webm,audio/*';
