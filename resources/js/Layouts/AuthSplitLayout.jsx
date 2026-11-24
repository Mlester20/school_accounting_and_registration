import { usePage } from '@inertiajs/react';

/**
 * Full-viewport split auth layout used by Login, ForceChangePassword and
 * ConfirmPassword.
 *
 * lg+  : fills the entire viewport (h-dvh, no outer padding, no floating card,
 *        no page scroll). Left half = gradient brand panel, right half = white
 *        panel with the vertically centered form. The right panel scrolls
 *        internally (overflow-y-auto) when the form is taller than the
 *        viewport, so nothing is ever clipped.
 * <lg  : single column, full-screen white background, brand above the heading,
 *        no gradient panel; the page scrolls naturally (e.g. keyboard opens).
 *
 * Each page sets its own <Head title="...">.
 */

/**
 * Inline SVG graduation-cap brand mark inside a rounded indigo tile.
 * Replaces the Laravel ApplicationLogo on the auth pages only.
 */
function BrandMark() {
    return (
        <div className="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-indigo-600 shadow-lg shadow-indigo-600/30 ring-1 ring-white/25">
            <svg
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
                className="h-6 w-6 text-white"
            >
                <path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3z" />
                <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" />
            </svg>
        </div>
    );
}

export default function AuthSplitLayout({
    children,
    status,
    title,
    subtitle,
}) {
    const appName = usePage().props.appName ?? 'ISU Portal';

    return (
        <div className="flex min-h-dvh flex-col bg-white lg:h-dvh lg:min-h-0 lg:flex-row lg:overflow-hidden">
            {/* LEFT: gradient brand panel (lg+ only) — three zones: brand / illustration / tagline */}
            <div className="hidden lg:flex lg:w-1/2 lg:flex-none lg:flex-col bg-gradient-to-br from-blue-600 via-indigo-600 to-indigo-800">
                {/* Top zone: brand — always on top, can never be overlapped */}
                <div className="relative z-10 flex items-center gap-3 px-12 pt-10">
                    <BrandMark />
                    <span className="text-lg font-semibold tracking-wide text-white">
                        {appName}
                    </span>
                </div>

                {/* Middle zone: CSS-only illustration in its own clipped container —
                    the decorative cards are positioned only inside it and can never
                    extend outside it, so they can never overlap the brand or tagline.
                    Hidden entirely on short viewports so text always has room. */}
                <div
                    className="relative min-h-0 flex-1 overflow-hidden [@media(max-height:640px)]:hidden"
                    aria-hidden="true"
                >
                    <div className="pointer-events-none absolute inset-0">
                        {/* Soft glow blobs */}
                        <div className="absolute -left-16 top-8 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
                        <div className="absolute -bottom-16 right-0 h-64 w-64 rounded-full bg-indigo-300/20 blur-3xl" />

                        {/* "Enrollment" card */}
                        <div className="absolute left-[10%] top-[6%] w-44 -rotate-6 rounded-xl bg-white/90 p-4 shadow-lg shadow-indigo-900/20">
                            <div className="flex items-center gap-2">
                                <div className="h-8 w-8 rounded-lg bg-indigo-100" />
                                <div className="h-2.5 w-20 rounded-full bg-indigo-200" />
                            </div>
                            <div className="mt-3 space-y-2">
                                <div className="h-2 w-full rounded-full bg-gray-200" />
                                <div className="h-2 w-3/4 rounded-full bg-gray-200" />
                            </div>
                            <div className="mt-3 inline-block rounded-full bg-green-100 px-2 py-0.5">
                                <div className="h-1.5 w-10 rounded-full bg-green-400" />
                            </div>
                        </div>

                        {/* "Grades" card with abstract bars */}
                        <div className="absolute right-[8%] top-[38%] w-48 rotate-3 rounded-xl bg-white/90 p-4 shadow-lg shadow-indigo-900/20">
                            <div className="h-2.5 w-16 rounded-full bg-indigo-200" />
                            <div className="mt-3 flex items-end gap-2">
                                <div className="h-8 w-5 rounded-sm bg-blue-300" />
                                <div className="h-12 w-5 rounded-sm bg-indigo-400" />
                                <div className="h-6 w-5 rounded-sm bg-blue-200" />
                                <div className="h-10 w-5 rounded-sm bg-indigo-500" />
                            </div>
                            <div className="mt-3 h-2 w-24 rounded-full bg-gray-200" />
                        </div>

                        {/* "Statement of account" card with lines */}
                        <div className="absolute bottom-[4%] left-[14%] w-52 -rotate-3 rounded-xl bg-white/90 p-4 shadow-lg shadow-indigo-900/20">
                            <div className="flex items-center justify-between">
                                <div className="h-2.5 w-20 rounded-full bg-indigo-200" />
                                <div className="h-4 w-10 rounded bg-indigo-100" />
                            </div>
                            <div className="mt-3 space-y-2">
                                <div className="flex justify-between">
                                    <div className="h-2 w-24 rounded-full bg-gray-200" />
                                    <div className="h-2 w-8 rounded-full bg-gray-300" />
                                </div>
                                <div className="flex justify-between">
                                    <div className="h-2 w-20 rounded-full bg-gray-200" />
                                    <div className="h-2 w-8 rounded-full bg-gray-300" />
                                </div>
                                <div className="mt-1 flex justify-between border-t border-gray-200 pt-2">
                                    <div className="h-2 w-16 rounded-full bg-indigo-300" />
                                    <div className="h-2 w-12 rounded-full bg-indigo-400" />
                                </div>
                            </div>
                        </div>

                        {/* Small floating accents */}
                        <div className="absolute right-[28%] top-[10%] h-10 w-10 rotate-12 rounded-xl bg-white/20" />
                        <div className="absolute bottom-[28%] left-[44%] h-6 w-6 rounded-full bg-white/15" />
                    </div>
                </div>

                {/* Bottom zone: tagline — mt-auto keeps it pinned to the bottom even
                    when the illustration zone is hidden on short viewports */}
                <div className="relative z-10 mt-auto px-12 pb-10 pt-6">
                    <h2 className="text-2xl font-bold leading-snug text-white">
                        Enrollment, grades, and accounting in one place.
                    </h2>
                    <p className="mt-2 text-sm text-blue-100">
                        For students, faculty, and staff of the university.
                    </p>
                </div>
            </div>

            {/* RIGHT: form panel — scrolls internally at lg+ if content is taller
                than the viewport; page scroll stays disabled */}
            <div className="flex w-full flex-1 flex-col lg:w-1/2 lg:flex-none lg:overflow-y-auto">
                <div className="flex flex-1 flex-col justify-center px-6 py-10 sm:px-10 lg:px-14 lg:py-8">
                    <div className="mx-auto w-full max-w-sm">
                        {/* Brand mark above the heading — both on mobile and lg+ */}
                        <div className="mb-5 flex items-center gap-3">
                            <BrandMark />
                            <span className="text-lg font-semibold text-gray-900">
                                {appName}
                            </span>
                        </div>

                        {title && (
                            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                        )}
                        {subtitle && (
                            <p className="mt-1 text-sm text-gray-600">{subtitle}</p>
                        )}

                        {status && (
                            <div className="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                                {status}
                            </div>
                        )}

                        <div className="mt-5">{children}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
