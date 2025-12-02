@php
    $authUser = Auth::guard('web')->user() ?? Auth::guard('focal')->user();
    
    if (!$authUser) {
        return;
    }
    
    $currentRole = session('current_role', $authUser->primary_role ?? $authUser->getRoleNames()->first());
@endphp

@switch($currentRole)
    @case('Focal_Person')
        <!-- Focal Person Navigation -->
        <a href="{{ route('FocalPerson.dvrequest.index') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('FocalPerson.dvrequest.index') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            My Documents
        </a>
        <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('FocalPerson.dvrequest.submitdvors') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Submit Document
        </a>
        <a href="{{ route('FocalPerson.dvrequest.history') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('FocalPerson.dvrequest.history') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Request History
        </a>
        @break

    @case('IMSD')
        <!-- IMSD Navigation -->
        <a href="{{ route('RegionalOffices.IMSD.index') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('RegionalOffices.IMSD.index') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Received Documents
        </a>
        <a href="{{ route('RegionalOffices.IMSD.redirectFirst') }}"
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('RegionalOffices.IMSD.redirectFirst') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Verify Disbursement Documents
        </a>
        <a href="{{ route('RegionalOffices.IMSD.history') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('RegionalOffices.IMSD.history') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            IMSD Log History
        </a>
        @break
     @case('Incoming-GIP')
         <div class="flex space-x-3">
                <a href="{{ route('Incoming.GIP.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.GIP.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Received Documents
                </a>
                                        <!-- Verify Documents -->
                <a href="{{ route('Incoming.GIP.verifygipdv') }}" 
                class="block py-2 px-4 rounded hover:bg-gray-200
                        {{ request()->routeIs('Incoming.GIP.verifygipdv.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Verify Documents
                </a>
                <a href="{{ route('dvrequests.giphistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('dvrequests.giphistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    Request History
                </a>
        
            </div>
        @break
    
     @case('Incoming-TUPAD')
           <div class="flex space-x-3">
                <a href="{{ route('Incoming.TUPAD.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.TUPAD.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Received Documents
                </a>
                                        <!-- Verify Documents -->
                <a href="{{ route('Incoming.TUPAD.verifytupaddv') }}" 
                class="block py-2 px-4 rounded hover:bg-gray-200
                        {{ request()->routeIs('Incoming.TUPAD.verifytupaddv.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Verify Documents
                </a>
                <a href="{{ route('dvrequests.tupadhistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('dvrequests.tupadhistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    Request History
                </a>
        
            </div>
        @break
        
     @case('Incoming-TEV')
        <!-- IMSD Navigation -->
       <div class="flex space-x-3">
                    <!-- Received Documents -->
                    <a href="{{ route('Incoming.TEV.index') }}" 
                    class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.TEV.index') ? 'bg-gray-200 font-semibold' : '' }}">
                        Received Documents
                    </a>

                    <!-- Verify Documents -->
                    <a href="{{ route('Incoming.TEV.verifytevdv') }}" 
                    class="block py-2 px-4 rounded hover:bg-gray-200
                            {{ request()->routeIs('Incoming.TEV.dvrequests.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                        Verify Documents
                    </a>

                    <!-- Request History -->
                    <a href="{{ route('dvrequests.tevhistory') }}" 
                    class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('dvrequests.tevhistory') ? 'bg-gray-200 font-semibold' : '' }}">
                        Request History
                    </a>
                </div>
        @break

    @case('Budget_Officer')
        <!-- Budget Officer Navigation -->
        <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('BudgetOfficer.dvrequests.index') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Received Documents
        </a>
        <a href="{{ route('BudgetOfficer.dvrequests.verifyors') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('BudgetOfficer.dvrequests.verifyors') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Verify Documents
        </a>
        <a href="{{ route('BudgetOfficer.dvrequests.loghistory') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('BudgetOfficer.dvrequests.loghistory') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            ORS Log History
        </a>
        @break
        
        
     @case('Outgoing')
      <a href="{{ route('Outgoing.dvrequests.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Pending DV Documents
                </a>
            <a href="{{ route('Outgoing.dvrequests.verifydv') }}"
                class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Finalize DV Documents
                </a>
                <a href="{{ route('Outgoing.dvrequests.dvloghistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.dvloghistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    DV Log History
                </a>
    @break
    
    @case('Accountant_III')
        <!-- Accounting III Navigation -->
        <a href="{{ route('Accounting.dvrequests.index') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('Accounting.dvrequests.index') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Received Documents
        </a>
        <a href="{{ route('Accounting.dvrequest.redirectFirstPending') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('Accounting.dvrequest.redirectFirstPending') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Verify Documents
        </a>
        <a href="{{ route('Accounting.history') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('Accounting.history') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            DV Log History
        </a>
        @break
        
           @case('Regional_Director')
        <!-- Accounting III Navigation -->
         <a href="{{ route('regional.director.dashboard') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('regional.director.dashboard') ? 'bg-gray-200 font-semibold' : '' }}">
              Regional Director Dashboard
            </a>
            <a href="{{ route('regional.director.scanner') }}"
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('regional.director.scanner') ? 'bg-gray-200 font-semibold' : '' }}">
              Regional Director QR Scanner
            </a>
            <a href="{{ route('regional.director.history') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('regional.director.history') ? 'bg-gray-200 font-semibold' : '' }}">
              Regional Director Approved History
            </a>
        @break
        
           @case('Cashier')
        <!-- Accounting III Navigation -->
          <a href="{{ route('cashier.dashboard') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('cashier.dashboard') ? 'bg-gray-200 font-semibold' : '' }}">
                Cashier Dashboard
            </a>
            <a href="{{ route('cashier.scanner') }}"
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('cashier.scanner') ? 'bg-gray-200 font-semibold' : '' }}">
              Cashier QR Scanner
            </a>
            <a href="{{ route('cashier.history') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('cashier.history') ? 'bg-gray-200 font-semibold' : '' }}">
              Cashier Released History
            </a>
        @break

    @case('Admin')
        <!-- Admin Navigation -->
        <a href="{{ route('Admin.dashboard') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('Admin.dashboard') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.accountingunits.index') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('admin.accountingunits.*') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            User Management
        </a>
        <a href="{{ route('admin.document-tracking') }}" 
           class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                  {{ request()->routeIs('admin.document-tracking') ? 
                     'bg-blue-100 text-blue-700' : 
                     'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            Document Tracking
        </a>
        @break

    <!-- Add other roles as needed -->
@default
    <!-- Default Navigation -->
    @if($authUser)
        @if(Route::has('index'))
            <a href="{{ route('index') }}" 
               class="px-4 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-all duration-200">
                Dashboard
            </a>
        @else
            <a href="{{ url('/') }}" 
               class="px-4 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-all duration-200">
                Dashboard
            </a>
        @endif
        @endif
@endswitch