<p class="text-[13px] font-medium text-slate-500">Order summary</p>
<p class="mt-3 font-display text-[15px] text-slate-900" x-text="serviceLabel"></p>
<p class="mt-1 text-[13px] text-slate-500" x-text="deadline ? deadline.name : 'Choose a deadline'"></p>
<p class="mt-1 text-[13px] text-slate-500" x-show="dueLabel" x-text="'Due ' + dueLabel"></p>
<p class="mt-3 text-[15px] tabular-nums text-slate-900" x-text="quantityLabel"></p>
<ul class="mt-4 space-y-2 border-t border-slate-200/70 pt-4 text-[13px]">
    <template x-for="line in filteredLines" :key="line.key">
        <li class="flex items-start justify-between gap-4">
            <span class="text-slate-600" x-text="line.label"></span>
            <span class="shrink-0 tabular-nums text-slate-900" x-text="money(line.amount)"></span>
        </li>
    </template>
    <li class="flex justify-between gap-4" x-show="discountLine">
        <span class="text-slate-600">Coupon</span>
        <span class="tabular-nums text-good" x-text="discountLine ? money(discountLine.amount) : ''"></span>
    </li>
</ul>
<p class="mt-5 text-[13px] text-slate-500">Total</p>
<p class="font-display text-[32px] leading-none tabular-nums tracking-tight text-navy" :class="bump && 'total-pop'" x-text="quote ? money(quote.amount_due) : (loading ? '…' : '$0.00')">$0.00</p>
<p class="mt-2 text-[13px] text-accent" x-show="error" x-text="error"></p>
<template x-for="note in (quote?.notes || [])" :key="note">
    <p class="mt-2 text-[13px] leading-5 text-slate-500" x-text="note"></p>
</template>
