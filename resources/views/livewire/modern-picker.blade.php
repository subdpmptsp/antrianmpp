<div class="w-full min-w-0">
@if ($type === 'time')
    @php
        $hours = collect(range(0, 23))->map(fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT))->all();
        $minutes = collect(range(0, 55, 5))->map(fn (int $minute) => str_pad((string) $minute, 2, '0', STR_PAD_LEFT))->all();
    @endphp

    <div
        class="relative w-full min-w-0"
        x-data="{
            open: false,
            value: $wire.entangle('value').live,
            hours: @js($hours),
            minutes: @js($minutes),
            panelStyle: '',
            part(index, fallback) {
                return (this.value || fallback).split(':')[index] || fallback.split(':')[index]
            },
            toggle() {
                this.open = ! this.open
                if (this.open) this.$nextTick(() => this.positionPanel())
            },
            positionPanel() {
                if (! this.open || ! this.$refs.trigger) return

                const trigger = this.$refs.trigger.getBoundingClientRect()
                const margin = 12
                const width = Math.min(288, window.innerWidth - (margin * 2))
                const panelHeight = Math.min(318, window.innerHeight - (margin * 2))
                const left = Math.max(margin, Math.min(trigger.left, window.innerWidth - width - margin))
                const spaceBelow = window.innerHeight - trigger.bottom - margin
                const top = spaceBelow >= panelHeight
                    ? trigger.bottom + 8
                    : Math.max(margin, trigger.top - panelHeight - 8)

                this.panelStyle = `position: fixed; z-index: 9999; width: ${width}px; left: ${left}px; top: ${top}px;`
            },
            selectHour(hour) {
                this.value = `${hour}:${this.part(1, '00:00')}`
            },
            selectMinute(minute) {
                this.value = `${this.part(0, '00:00')}:${minute}`
                this.open = false
            },
        }"
        x-on:keydown.escape.window="open = false"
        x-on:resize.window="positionPanel()"
    >
        @if (filled($label))
            <label class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</label>
        @endif

        <button
            x-ref="trigger"
            type="button"
            class="fi-input-wrp flex min-h-[2.75rem] w-full items-center justify-between gap-x-3 rounded-lg border border-gray-300 bg-white px-3 text-left text-sm text-gray-950 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            style="min-height: 42px; padding-top: 8px; padding-bottom: 8px; line-height: 1.35;"
            x-on:click="toggle()"
            x-bind:aria-expanded="open"
            @disabled($disabled)
        >
            <span x-text="value || 'Pilih waktu'" :class="{ 'text-gray-400 dark:text-gray-500': ! value }"></span>
            <span class="flex items-center gap-2 text-gray-400">
                <span x-show="value" x-on:click.stop="value = null" class="cursor-pointer text-lg leading-none" aria-label="Kosongkan waktu">×</span>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 7 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </button>

        <div
            x-ref="panel"
            x-cloak
            x-show="open"
            x-transition.origin.top.left
            x-on:click.outside="open = false"
            x-bind:style="panelStyle"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-950 dark:text-white">Pilih waktu</span>
                <button type="button" x-on:click="open = false" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800" aria-label="Tutup pemilih waktu">×</button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Jam</p>
                    <div
                        class="space-y-1 rounded-lg bg-gray-50 p-1.5 dark:bg-gray-800"
                        style="height: min(240px, 48vh); overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable;"
                    >
                        <template x-for="hour in hours" :key="hour">
                            <button
                                type="button"
                                class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                                x-on:click="selectHour(hour)"
                                x-text="hour"
                                :class="part(0, '00:00') === hour ? 'bg-primary-600 text-white' : 'text-gray-700 hover:bg-white dark:text-gray-200 dark:hover:bg-gray-700'"
                            ></button>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Menit</p>
                    <div
                        class="space-y-1 rounded-lg bg-gray-50 p-1.5 dark:bg-gray-800"
                        style="height: min(240px, 48vh); overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable;"
                    >
                        <template x-for="minute in minutes" :key="minute">
                            <button
                                type="button"
                                class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                                x-on:click="selectMinute(minute)"
                                x-text="minute"
                                :class="part(1, '00:00') === minute ? 'bg-primary-600 text-white' : 'text-gray-700 hover:bg-white dark:text-gray-200 dark:hover:bg-gray-700'"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    {{ $this->form }}
@endif
</div>
