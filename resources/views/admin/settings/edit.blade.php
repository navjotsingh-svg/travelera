<x-admin-layout title="Settings">
    <section class="admin-panel" style="max-width: 560px;">
        <h2 class="admin-panel-title">Platform fee</h2>
        <p class="admin-muted" style="margin-top: -4px; margin-bottom: 18px;">
            Added on top of the airline or listing price when customers pay. Duffel / supplier cost is unchanged.
        </p>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
            @csrf
            @method('PATCH')

            <label>
                Platform fee (%)
                <input
                    type="number"
                    name="platform_fee_percent"
                    value="{{ old('platform_fee_percent', number_format($platformFeePercent, 2, '.', '')) }}"
                    min="0"
                    max="100"
                    step="0.01"
                    required
                >
            </label>

            @error('platform_fee_percent')
                <p class="admin-error">{{ $message }}</p>
            @enderror

            <p class="admin-muted">
                Example: fare $100 with
                <strong>{{ number_format($platformFeePercent, 2) }}%</strong>
                fee → customer pays
                <strong>${{ number_format(100 + (100 * $platformFeePercent / 100), 2) }}</strong>.
            </p>

            <button type="submit" class="admin-btn">Save settings</button>
        </form>
    </section>
</x-admin-layout>
