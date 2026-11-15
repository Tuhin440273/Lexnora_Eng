import { Form, Head } from '@inertiajs/react';
import LearningSettingsController from '@/actions/App/Modules/Learning/Http/Controllers/LearningSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';

type Props = {
    profile: {
        display_name: string | null;
        target_cefr_level: string | null;
        daily_goal_minutes: number;
        daily_goal_sentences: number;
        timezone: string;
        leaderboard_anonymous: boolean;
        default_mode: string;
        default_speed: number;
        autoplay: boolean;
    };
    options: {
        cefr_levels: { value: string; label: string }[];
        modes: { value: string; label: string }[];
        speeds: number[];
        timezones: string[];
    };
};

export default function LearningSettings({ profile, options }: Props) {
    return (
        <>
            <Head title="Learning settings" />
            <h1 className="sr-only">Learning settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Learning"
                    description="Your goals, practice defaults and what others see on the leaderboard"
                />

                <Form
                    {...LearningSettingsController.update.form()}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="display_name">
                                    Display name
                                </Label>
                                <Input
                                    id="display_name"
                                    name="display_name"
                                    defaultValue={profile.display_name ?? ''}
                                    maxLength={60}
                                    placeholder="Shown on the leaderboard (optional)"
                                />
                                <InputError message={errors.display_name} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="daily_goal_minutes">
                                        Daily goal (minutes)
                                    </Label>
                                    <Input
                                        id="daily_goal_minutes"
                                        name="daily_goal_minutes"
                                        type="number"
                                        min={1}
                                        max={240}
                                        defaultValue={
                                            profile.daily_goal_minutes
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.daily_goal_minutes}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="daily_goal_sentences">
                                        Daily goal (sentences)
                                    </Label>
                                    <Input
                                        id="daily_goal_sentences"
                                        name="daily_goal_sentences"
                                        type="number"
                                        min={1}
                                        max={500}
                                        defaultValue={
                                            profile.daily_goal_sentences
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.daily_goal_sentences}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="target_cefr_level">
                                        Target level
                                    </Label>
                                    <NativeSelect
                                        id="target_cefr_level"
                                        name="target_cefr_level"
                                        defaultValue={
                                            profile.target_cefr_level ?? ''
                                        }
                                    >
                                        <option value="">Not set</option>
                                        {options.cefr_levels.map((level) => (
                                            <option
                                                key={level.value}
                                                value={level.value}
                                            >
                                                {level.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={errors.target_cefr_level}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Time zone</Label>
                                    <NativeSelect
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={profile.timezone}
                                    >
                                        {options.timezones.map((zone) => (
                                            <option key={zone} value={zone}>
                                                {zone}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <p className="text-xs text-muted-foreground">
                                        Decides when your day, streak and
                                        check-in start.
                                    </p>
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="default_mode">
                                        Preferred practice mode
                                    </Label>
                                    <NativeSelect
                                        id="default_mode"
                                        name="default_mode"
                                        defaultValue={profile.default_mode}
                                    >
                                        {options.modes.map((mode) => (
                                            <option
                                                key={mode.value}
                                                value={mode.value}
                                            >
                                                {mode.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.default_mode} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="default_speed">
                                        Default audio speed
                                    </Label>
                                    <NativeSelect
                                        id="default_speed"
                                        name="default_speed"
                                        defaultValue={String(
                                            profile.default_speed,
                                        )}
                                    >
                                        {options.speeds.map((speed) => (
                                            <option key={speed} value={speed}>
                                                {speed}x
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={errors.default_speed}
                                    />
                                </div>
                            </div>

                            <label className="flex items-start gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="autoplay"
                                    value="1"
                                    defaultChecked={profile.autoplay}
                                    className="mt-0.5 size-4 accent-primary"
                                />
                                <span>
                                    Play the audio by itself when a new item
                                    appears
                                    <span className="block text-xs text-muted-foreground">
                                        You can always press Play or Replay.
                                    </span>
                                </span>
                            </label>

                            <label className="flex items-start gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="leaderboard_anonymous"
                                    value="1"
                                    defaultChecked={
                                        profile.leaderboard_anonymous
                                    }
                                    className="mt-0.5 size-4 accent-primary"
                                />
                                <span>
                                    Show me as &ldquo;Anonymous learner&rdquo;
                                    on the leaderboard
                                    <span className="block text-xs text-muted-foreground">
                                        You always see your own row as
                                        &ldquo;You&rdquo;.
                                    </span>
                                </span>
                            </label>

                            <Button type="submit" disabled={processing}>
                                Save settings
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

LearningSettings.layout = {
    breadcrumbs: [
        { title: 'Learning settings', href: LearningSettingsController.edit() },
    ],
};
