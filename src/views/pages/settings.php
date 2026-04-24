<?php
/** @var array $settings */
?>
<a href="/invoices" class="inline-flex items-center gap-1 text-cream/70 hover:text-cream text-sm mb-4">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    Invoices
</a>

<h1 class="font-display text-2xl mb-2">Settings</h1>
<p class="text-cream/70 text-sm mb-5">These show on every invoice you send.</p>

<div class="card">
    <form method="post" action="/settings" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="label !text-navy-500" for="business_name">Business name</label>
            <input class="input" id="business_name" name="business_name" required
                   value="<?= e($settings['business_name'] ?? '') ?>">
        </div>
        <div>
            <label class="label !text-navy-500" for="contact_name">Contact name</label>
            <input class="input" id="contact_name" name="contact_name"
                   value="<?= e($settings['contact_name'] ?? '') ?>">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label !text-navy-500" for="email">Email</label>
                <input class="input" id="email" name="email" type="email"
                       value="<?= e($settings['email'] ?? '') ?>">
            </div>
            <div>
                <label class="label !text-navy-500" for="phone">Phone</label>
                <input class="input" id="phone" name="phone" type="tel"
                       value="<?= e($settings['phone'] ?? '') ?>">
            </div>
        </div>
        <div>
            <label class="label !text-navy-500" for="address">Address</label>
            <textarea class="input min-h-[5rem]" id="address" name="address"
                      placeholder="Street&#10;City, State ZIP"><?= e($settings['address'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="label !text-navy-500" for="payment_instructions">Payment instructions</label>
            <textarea class="input min-h-[6rem]" id="payment_instructions" name="payment_instructions"
                      placeholder="Venmo @chiefofstuff or check payable to…"><?= e($settings['payment_instructions'] ?? '') ?></textarea>
        </div>
        <button class="btn-primary w-full" data-loading-label="Saving…" type="submit">Save settings</button>
    </form>
</div>
