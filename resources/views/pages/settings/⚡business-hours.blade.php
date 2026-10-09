<?php

use App\Actions\BusinessHours\UpdateBusinessHours;
use Livewire\Component;

new class extends Component {
    public array $hours = [];

    public ?string $status = null;

    public function mount(): void
    {
        $businessHours = auth()->user()->businessHours()->get()->keyBy('day_of_week');

        for ($day = 0; $day <= 6; $day++) {
            $businessHour = $businessHours->get($day);

            if ($businessHour) {
                $this->hours[$day] = [
                    'is_closed' => $businessHour->is_closed,
                    'opens_at' => $businessHour->opens_at ? substr($businessHour->opens_at, 0, 5) : null,
                    'closes_at' => $businessHour->closes_at ? substr($businessHour->closes_at, 0, 5) : null,
                ];

                continue;
            }

            $this->hours[$day] = [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ];
        }
    }

    public function dayName(int $day): string
    {
        return match ($day) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        };
    }

    protected function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*' => ['required', 'array'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function save(UpdateBusinessHours $updateBusinessHours): void
    {
        $this->validate();

        try {
            $updateBusinessHours->update(user: auth()->user(), data: $this->hours);
        } catch (InvalidArgumentException $exception) {
            $this->addError('hours', $exception->getMessage());

            return;
        }

        $this->status = 'Business hours have been updated.';
    }
};
?>

<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">
            Business hours
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            Set the hours when customers can book your services.
        </p>
    </div>

    @if ($status)
        <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $status }}
        </div>
    @endif

    @if ($errors->has('hours'))
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('hours') }}
        </div>
    @endif

    <form wire:submit="save" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="divide-y divide-gray-200">
            @foreach ($hours as $day => $dayHours)
                <div class="px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-32">
                            <h2 class="font-medium text-gray-900">
                                {{ $this->dayName($day) }}
                            </h2>
                        </div>

                        <div class="flex flex-1 flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" wire:model.live="hours.{{ $day }}.is_closed"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                                <span class="text-sm text-gray-700">
                                    Closed
                                </span>
                            </label>

                            <div class="flex items-center gap-3" @if ($dayHours['is_closed']) hidden @endif>
                                <label class="sr-only" for="opens-at-{{ $day }}">
                                    Opening time
                                </label>

                                <input id="opens-at-{{ $day }}" type="time"
                                    wire:model="hours.{{ $day }}.opens_at"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                                <span class="text-sm text-gray-500">
                                    to
                                </span>

                                <label class="sr-only" for="closes-at-{{ $day }}">
                                    Closing time
                                </label>

                                <input id="closes-at-{{ $day }}" type="time"
                                    wire:model="hours.{{ $day }}.closes_at"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="save">
                    Save changes
                </span>

                <span wire:loading wire:target="save">
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
