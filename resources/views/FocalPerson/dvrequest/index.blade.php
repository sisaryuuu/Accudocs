@extends('layouts.app')

@section('title', 'My DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and track your disbursement voucher requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">

                <!-- Notification Dropdown -->
                <div class="relative">
                    <button id="notification-button" class="relative focus:outline-none bg-white border rounded-full p-2 shadow">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notification-badge" 
                            class="absolute top-0 right-0 inline-flex items-center justify-center px-1 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">0</span>
                    </button>
<ul id="notification-list" 
    class="hidden absolute right-0 mt-2 w-[500px] max-h-96 overflow-y-auto bg-white shadow-lg rounded-md z-50">

    <li class="p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer font-bold whitespace-normal break-words">
      
    </li>
</ul>
                </div>

                <!-- New DV Request Button -->
                <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New DV Request
                </a>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($dvRequests->isEmpty())
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No requests yet</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first DV request.</p>
                    <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Create Request
                    </a>
                </div>
            @else
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Request History</h3>
                    <div class="relative">
                        <input type="text" id="search" placeholder="Search Control No., Payee, Program..."
                               class="w-72 pl-10 pr-4 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200" id="dvTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DV Number</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payee Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TIN/Employee No.</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsibility Center</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Purpose</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Mode of Payment</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Particulars</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($dvRequests as $request)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $request->control_no ?? $request->id }}
                                </td>
                               <td id="dv-{{ $request->id }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($request->dv_number)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            {{ $request->dv_number }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $request->focalperson->fname }} {{ $request->focalperson->mi }} {{ $request->focalperson->lname }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $request->focalperson->TIN_number ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $request->respocenter->rccode ?? 'N/A' }} - {{ $request->respocenter->description ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $request->program->prcode ?? 'N/A' }}
                                    </span>
                                </td>
                               <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @if($request->modeOfPayments && $request->modeOfPayments->count() > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                            {{ $request->modeOfPayments->pluck('mopname')->join(', ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ₱{{ number_format($request->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs text-center">
                                    <div class="truncate px-3 py-1 font-medium" title="{{ $request->particulars ?? '' }}">
                                        {{ Str::limit($request->particulars ?? '', 50) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                <span id="status-{{ $request->id }}" 
                                    data-status="{{ $request->status }}" 
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-center">
                                    <span class="status-label">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span>
                                </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <div>{{ $request->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $request->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('FocalPerson.dvrequest.logs', $request->id) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200">
                                            Track
                                        </a>
                                        @if($request->status === 'submitted')
                                        <form id="delete-btn-{{ $request->id }}" action="{{ route('FocalPerson.dvrequest.destroy', $request->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors duration-200"
                                                onclick="return confirm('Are you sure you want to delete this request?')">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                     @if($request->status === 'ORS_for_revision')
                                            <a href="{{ route('FocalPerson.dvrequest.edit', $request->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-xs font-medium rounded-md transition-colors duration-200">
                                                Edit
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ $dvRequests->count() }} of {{ $dvRequests->count() }} requests
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Search Script -->
<script>
document.getElementById('search').addEventListener('keyup', function() {
    let query = this.value.toLowerCase();
    let rows = document.querySelectorAll('#dvTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});
</script>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('notification-list');
    const badge = document.getElementById('notification-badge');
    const notifButton = document.getElementById('notification-button');

    // Toast setup
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });

    // 🔧 Reusable render function for notifications
    function renderNotification(n) {
        const url = "{{ url('FocalPerson/dvrequest') }}/" + n.dv_request_id + "/logs";
        const timestamp = n.timestamp ? n.timestamp : new Date(n.created_at).toLocaleString();
        return `
            <a href="${url}" class="block w-full h-full">
                [${timestamp}] #${n.dv_request_id} ${n.status.toUpperCase()} - ${n.remarks}
            </a>
        `;
    }

    // 🔹 Initial fetch notifications
    try {
        const res = await fetch('{{ route("FocalPerson.notifications.fetch") }}');
        const notifications = await res.json();
        let unreadCount = 0;
        list.innerHTML = '';

        notifications.forEach(n => {
            if (!n.read_at) unreadCount++;
            const li = document.createElement('li');
            li.className = `p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer ${!n.read_at ? 'font-bold' : ''}`;
            li.dataset.id = n.id;
            li.innerHTML = renderNotification(n);
            list.appendChild(li);
        });

        badge.textContent = unreadCount;
    } catch (err) {
        console.error('Failed to fetch notifications:', err);
        list.innerHTML = '<li class="p-2 text-red-500">Failed to load notifications</li>';
    }

    // 🔹 Pusher real-time updates
    Pusher.logToConsole = true;
    const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
        forceTLS: true
    });

    const userId = '{{ Auth::guard("focal")->id() }}';
    const channel = pusher.subscribe('dv-requests-' + userId);

    // Helper function: update table & show toast if DV number is new
   function updateDVStatus(dv) {
    const statusEl = document.getElementById('status-' + dv.dv_request_id);
    const dvEl = document.getElementById('dv-' + dv.dv_request_id);
    const deleteBtn = document.getElementById('delete-btn-' + dv.dv_request_id);

    // 🔹 Update status text
    if (statusEl) {
        const label = statusEl.querySelector('.status-label');
        if (label) {
            label.textContent = dv.status.charAt(0).toUpperCase() + dv.status.slice(1).replace('_', ' ');
        }
        statusEl.dataset.status = dv.status.toLowerCase();
    }

    // 🔹 Update DV number cell
    if (dvEl) {
        if (dv.dv_number) {
            // Assign DV number
            dvEl.innerHTML = `
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                    ${dv.dv_number}
                </span>
            `;
            dvEl.dataset.dvNumber = dv.dv_number;
        } else {
            // Show Pending if still no DV number
            dvEl.innerHTML = `
                <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                    Pending
                </span>
            `;
            dvEl.dataset.dvNumber = '';
        }
    }

    // 🔹 Handle delete button visibility - show only if status is 'submitted'
    if (deleteBtn) {
        if (dv.status === 'submitted') {
            deleteBtn.style.display = 'inline-block';
        } else {
            deleteBtn.style.display = 'none';
        }
    }
}
    // Pusher binding
    channel.unbind('status-updated');
    channel.bind('status-updated', function(data) {
        console.log('Received real-time notification:', data);

        // Update notifications list
        if (list && badge) {
            let count = parseInt(badge.textContent) || 0;
            badge.textContent = count + 1;
            const li = document.createElement('li');
            li.className = 'p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer font-bold';
            li.dataset.id = data.id;
            li.innerHTML = renderNotification(data);
            list.prepend(li);
        }
        
        Toast.fire({
        icon: 'info',
        title: 'DV Request Status Update',
        text: `#${data.dv_request_id} ${data.status.toUpperCase()} - ${data.remarks || ''}`
        });
        updateDVStatus(data);
    });

    // 🔹 Mark notifications as read individually
    list.addEventListener('click', async (e) => {
        const li = e.target.closest('li');
        if (!li) return;
        const id = li.dataset.id;
        if (!id) return;

        try {
            const response = await fetch(`{{ url('FocalPerson/notifications/read') }}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                li.classList.remove('font-bold');
                let count = parseInt(badge.textContent) || 0;
                badge.textContent = Math.max(count - 1, 0);
            }
        } catch (err) {
            console.error('Failed to mark notification as read:', err);
        }
    });

    // 🔹 Toggle notification dropdown + mark all as read
    if (notifButton) {
        notifButton.addEventListener('click', async () => {
            list.classList.toggle('hidden');
            if (!list.classList.contains('hidden')) {
                try {
                    await fetch('{{ route("FocalPerson.notifications.markAllAsRead") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    });
                    badge.textContent = '0';
                    list.querySelectorAll('li').forEach(li => li.classList.remove('font-bold'));
                } catch (err) {
                    console.error('Failed to mark all as read:', err);
                }
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!notifButton.contains(e.target) && !list.contains(e.target)) {
            list.classList.add('hidden');
        }
    });

    // 🔹 Auto-refresh DV requests every 60s
    setInterval(async () => {
        try {
            const res = await fetch('{{ route("FocalPerson.dvrequest.refresh") }}');
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const updated = await res.json();

            updated.forEach(dv => updateDVStatus(dv));
        } catch (err) {
            console.error("Auto-refresh failed:", err);
        }
    }, 60000);
});
</script>



@endsection
