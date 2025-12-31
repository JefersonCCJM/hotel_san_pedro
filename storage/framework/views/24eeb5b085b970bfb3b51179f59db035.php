<?php $__env->startSection('title', 'Habitaciones'); ?>

<?php $__env->startSection('header', 'Habitaciones'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('room-manager', ['date' => request('date'),'search' => request('search'),'status' => request('status')]);

$key = null;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3057757238-0', null);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    
    .ts-wrapper.single .ts-control { border-radius: 0.75rem !important; padding: 0.75rem 1.25rem !important; border: 1px solid #e5e7eb !important; background-color: #f9fafb !important; font-size: 0.875rem; font-weight: 700; }
    .ts-dropdown { border-radius: 1rem !important; margin-top: 8px !important; border: 1px solid #f3f4f6 !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important; padding: 0.5rem !important; }
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\crist\Documents\aparte\hotel_san_pedro\resources\views/rooms/index.blade.php ENDPATH**/ ?>