@props([
    'room' => [],
    'status' => [],
])

<div class="room-card bg-white rounded-2xl shadow-lg border-3 {{ $status['border'] }} {{ $status['color'] }} transition-all duration-300 hover:transform hover:-translate-y-1">
    <div class="p-5 sm:p-6">
        <!-- Room Number -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 rounded-xl {{ $status['badge'] }}">
                    <i class="fas {{ $status['icon'] }} text-2xl {{ $status['color'] }}"></i>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black {{ $status['color'] }}">#{{ $room['room_number'] }}</h3>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="mb-5">
            <x-cleaning.status-badge :status="$status" />
        </div>

        <!-- Room Info -->
        <div class="space-y-3 mb-5">
            <div class="flex items-center space-x-3 text-base font-semibold {{ $status['color'] }}">
                <i class="fas fa-bed"></i>
                <span>{{ $room['beds_count'] }} {{ $room['beds_count'] === 1 ? 'cama' : 'camas' }}</span>
            </div>
            <div class="flex items-center space-x-3 text-base font-semibold {{ $status['color'] }}">
                <i class="fas fa-users"></i>
                <span>Capacidad: {{ $room['max_capacity'] }} personas</span>
            </div>
        </div>

        <!-- Action Button -->
        <x-cleaning.action-button 
            :roomId="$room['id']"
            :roomNumber="$room['room_number']"
            :canMarkClean="$room['can_mark_clean']"
            :status="$status" />
    </div>
</div>

