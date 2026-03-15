<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\FocalPerson;
use App\Models\Program;
use App\Models\ResponCenter;
use App\Models\ModeOfPayment;
use App\Models\DvRequest;
use App\Models\DvRequestModeOfPayment;
use App\Models\PaymentDetail;
use App\Models\DvRequestLog;
use Carbon\Carbon;

class DummyDvRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Create Focal Persons
            $focalPersons = $this->createFocalPersons();
            
            // Create Programs
            $programs = $this->createPrograms();
            
            // Create Responsibility Centers
            $respoCenters = $this->createRespoCenters();
            
            // Create Mode of Payments
            $modeOfPayments = $this->createModeOfPayments();
            
            // Create DV Requests with various statuses
            $this->createDvRequests($focalPersons, $programs, $respoCenters, $modeOfPayments);
            
            DB::commit();
            
            $this->command->info('✅ DV Request seeder completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create Focal Persons
     */
    private function createFocalPersons(): array
    {
        $this->command->info('Creating/Using Focal Persons...');
        
        // Check if focal persons already exist
        $existingFocals = FocalPerson::all();
        
        if ($existingFocals->count() > 0) {
            $this->command->info('✓ Using ' . $existingFocals->count() . ' existing focal persons');
            return $existingFocals->all();
        }
        
        // If no focal persons exist, create new ones
        $focalPersons = [
            [
                'fname' => 'Juan',
                'mi' => 'D',
                'lname' => 'Cruz',
                'email' => 'juan.cruz@dole.gov.ph',
                'password' => bcrypt('password'),
                'TIN_number' => '123-456-789-000',
                'Address' => 'Davao City, Philippines',
                'contactnumber' => '09123456789',
                'respocenter_id' => 1,
            ],
            [
                'fname' => 'Maria',
                'mi' => 'S',
                'lname' => 'Santos',
                'email' => 'maria.santos@dole.gov.ph',
                'password' => bcrypt('password'),
                'TIN_number' => '987-654-321-000',
                'Address' => 'Tagum City, Philippines',
                'contactnumber' => '09123456790',
                'respocenter_id' => 2,
            ],
            [
                'fname' => 'Pedro',
                'mi' => 'L',
                'lname' => 'Reyes',
                'email' => 'pedro.reyes@dole.gov.ph',
                'password' => bcrypt('password'),
                'TIN_number' => '456-789-123-000',
                'Address' => 'Mati City, Philippines',
                'contactnumber' => '09123456791',
                'respocenter_id' => 3,
            ]
        ];
        
        $created = [];
        foreach ($focalPersons as $data) {
            $created[] = FocalPerson::firstOrCreate(
                ['email' => $data['email']], 
                $data
            );
        }
        
        $this->command->info('✓ Created ' . count($created) . ' new focal persons');
        return $created;
    }
    
    /**
     * Create Programs
     */
    private function createPrograms(): array
    {
        $this->command->info('Creating/Using Programs...');
        
        $existingPrograms = Program::all();
        
        if ($existingPrograms->count() > 0) {
            $this->command->info('✓ Using ' . $existingPrograms->count() . ' existing programs');
            return $existingPrograms->all();
        }
        
        $programs = [
            ['prcode' => 'TUPAD', 'description' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers'],
            ['prcode' => 'GIP', 'description' => 'Government Internship Program'],
            ['prcode' => 'TEV', 'description' => 'Travel Expenses Voucher'],
            ['prcode' => 'SPES', 'description' => 'Special Program for Employment of Students'],
            ['prcode' => 'KABUHAYAN', 'description' => 'Kabuhayan Development Program'],
        ];
        
        $created = [];
        foreach ($programs as $data) {
            $created[] = Program::firstOrCreate(
                ['prcode' => $data['prcode']], 
                $data
            );
        }
        
        $this->command->info('✓ Created ' . count($created) . ' programs');
        return $created;
    }
    
    /**
     * Create Responsibility Centers
     */
    private function createRespoCenters(): array
    {
        $this->command->info('Creating/Using Responsibility Centers...');
        
        $existingRespoCenters = ResponCenter::all();
        
        if ($existingRespoCenters->count() > 0) {
            $this->command->info('✓ Using ' . $existingRespoCenters->count() . ' existing responsibility centers');
            return $existingRespoCenters->all();
        }
        
        $centers = [
            ['rccode' => '16-001-03-00011-01-01', 'description' => 'OR'],
            ['rccode' => '16-001-03-00011-01-02', 'description' => 'OARD'],
            ['rccode' => '16-001-03-00011-01-01-01', 'description' => 'MALSU'],
            ['rccode' => '16-001-03-00011-02-01', 'description' => 'IMSD'],
            ['rccode' => '16-001-03-00011-02-02', 'description' => 'TSSD'],
            ['rccode' => '16-001-03-00011-03-01', 'description' => 'DCFO'],
            ['rccode' => '16-001-03-00011-03-02', 'description' => 'DNFO'],
            ['rccode' => '16-001-03-00011-03-03', 'description' => 'DOFO'],
            ['rccode' => '16-001-03-00011-03-04', 'description' => 'DSFO'],
            ['rccode' => '16-001-03-00011-03-05', 'description' => 'DOCFO'],
            ['rccode' => '16-001-03-00011-03-06', 'description' => 'DORFO'],
            ['rccode' => '16-001-03-00011-03-07', 'description' => 'DIEO'],
            ['rccode' => '16-001-03-00011-03-08', 'description' => 'DBFO'],
        ];
        
        $created = [];
        foreach ($centers as $data) {
            $created[] = ResponCenter::firstOrCreate(
                ['rccode' => $data['rccode']], 
                $data
            );
        }
        
        $this->command->info('✓ Created ' . count($created) . ' responsibility centers');
        return $created;
    }
    
    /**
     * Create Mode of Payments
     */
    private function createModeOfPayments(): array
    {
        $this->command->info('Using existing Mode of Payments...');
        
        // Use existing mode of payments from database
        $existing = ModeOfPayment::all();
        
        if ($existing->count() > 0) {
            $this->command->info('✓ Found ' . $existing->count() . ' existing mode of payments');
            return $existing->all();
        }
        
        // Fallback: create if none exist
        $modes = [
            ['mopname' => 'MDS Check'],
            ['mopname' => 'Commercial Check'],
            ['mopname' => 'ADA'],
        ];
        
        $created = [];
        foreach ($modes as $data) {
            $created[] = ModeOfPayment::firstOrCreate($data);
        }
        
        $this->command->info('✓ Created ' . count($created) . ' mode of payments');
        return $created;
    }
    
    /**
     * Create DV Requests for January-February 2026 with various statuses
     */
    private function createDvRequests($focalPersons, $programs, $respoCenters, $modeOfPayments): void
    {
        $this->command->info('Creating DV Requests for January-February 2026 with various statuses...');
        
        $particularsTemplates = [
            'Payment for training materials and supplies',
            'Wages for TUPAD beneficiaries - Phase 1',
            'Procurement of office equipment',
            'Professional fees for consultancy services',
            'Travel expenses for monitoring activities',
            'Food and accommodation for training participants',
            'Honorarium for resource speakers',
            'Internet and communication expenses',
            'Monthly allowance for interns',
            'Operational expenses for field offices',
            'Payment for janitorial services',
            'Security services for DOLE office',
            'Transportation allowance for staff',
            'Communication expenses for project implementation',
            'Office supplies for quarter 1',
            'Maintenance of office equipment',
            'Training expenses for livelihood program',
            'Cash assistance for displaced workers',
            'Medical assistance for beneficiaries',
            'Funeral assistance program',
        ];
        
        $statuses = [
            'submitted',
            'IMSD_received',
            'IMSD_verified',
            'Incoming-GIP_verified',
            'Incoming-TUPAD_verified',
            'Incoming-TEV_verified',
            'ORS_obligated',
            'Outgoing_finalized',
            'RANCA_validated'
        ];
        
        // Total number of requests to create (100 total for Jan-Feb 2026)
        $totalRequests = 100;
        $userSequence = 200;
        $createdCount = 0;
        
        // Create 100 requests spread throughout January and February 2026
        for ($i = 0; $i < $totalRequests; $i++) {
            $focal = $focalPersons[array_rand($focalPersons)];
            $program = $programs[array_rand($programs)];
            $respoCenter = $respoCenters[array_rand($respoCenters)];
            $mop = $modeOfPayments[array_rand($modeOfPayments)];
            
            // Generate reference number: DVORS-XXXXXXXX (8 random uppercase characters)
            do {
                $referenceNumber = 'DVORS-' . strtoupper(Str::random(8));
            } while (DvRequest::where('reference_number', $referenceNumber)->exists());
            
            // Distribute evenly: 50 requests in January, 50 in February
            $month = ($i < 50) ? 1 : 2; // First 50 in Jan, next 50 in Feb
            
            // Generate dates within January or February 2026
            $maxDay = ($month == 1) ? 31 : 28; // February 2026 has 28 days
            $day = rand(1, $maxDay);
            
            // Time of day (business hours)
            $hour = rand(8, 17);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            // Create base date
            $createdAt = Carbon::create(2026, $month, $day, $hour, $minute, $second);
            
            $updatedAt = $createdAt->copy()->addHours(rand(1, 48));
            
            // Generate Accounting Control Number (simple incrementing number)
            $accountingControlNumber = $userSequence;
            
            // Select a random status from the statuses array
            $status = $statuses[array_rand($statuses)];
            
            // DV number should be null for submitted requests, generate for later statuses
            $dvNumber = null;
            if (in_array($status, ['ORS_obligated', 'Outgoing_finalized', 'RANCA_validated'])) {
                $dvNumber = 'DV-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
            
            // Set confirmation flags - all requests should be confirmed by focal person
            $confirmedByFocalPerson = true;
            $confirmedAt = $createdAt->copy()->addHours(rand(1, 24));
            
            // QR code generation with error handling
            $qrCodePath = null;
            try {
                $qrFileName = $referenceNumber . '.png';
                $qrStoragePath = storage_path('app/public/qr_codes/' . $qrFileName);
                $verifyUrl = url('dv/verify/' . $referenceNumber);
            
                // Create directory if it doesn't exist
                $directory = dirname($qrStoragePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
            
                // Use external QR API
                $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&format=png&data=' . urlencode($verifyUrl);
                $qrCodeContent = @file_get_contents($apiUrl);
            
                if ($qrCodeContent === false) {
                    // Fallback: Try alternative API
                    $apiUrl2 = 'https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=' . urlencode($verifyUrl);
                    $qrCodeContent = @file_get_contents($apiUrl2);
                }
            
                if ($qrCodeContent !== false) {
                    file_put_contents($qrStoragePath, $qrCodeContent);
            
                    if (file_exists($qrStoragePath) && filesize($qrStoragePath) > 0) {
                        $qrCodePath = '/qr_codes/' . $qrFileName;
                    }
                }
            } catch (\Exception $qrError) {
                $this->command->warn('QR Code generation failed for ' . $referenceNumber . ': ' . $qrError->getMessage());
            }
                            
            // Create DV Request
            $dvRequest = DvRequest::create([
                'focalperson_id' => $focal->id,
                'reference_number' => $referenceNumber,
                'accounting_control_number' => $accountingControlNumber,
                'user_sequence' => $userSequence++,
                'program_id' => $program->id,
                'respocenter_id' => $respoCenter->id,
                'amount' => rand(8900, 18200),
                'particulars' => $particularsTemplates[array_rand($particularsTemplates)],
                'status' => $status,
                'dv_number' => $dvNumber,
                'qr_code_path' => $qrCodePath,
                'confirmed_by_focal_person' => $confirmedByFocalPerson,
                'confirmed_at' => $confirmedAt,
                'locked_by' => null,
                'locked_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
            
            // Attach Mode of Payment
            try {
                $dvRequestMopId = DB::table('dv_request_mode_of_payment')->insertGetId([
                    'dv_request_id' => $dvRequest->id,
                    'mode_of_payment_id' => $mop->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if (!$dvRequestMopId) {
                    throw new \Exception("Failed to insert DvRequestModeOfPayment");
                }
                
                // Create Payment Details only for MDS Check and ADA
                if (in_array($mop->mopname, ['MDS Check', 'ADA'])) {
                    DB::table('payment_details')->insert([
                        'dv_request_mode_of_payment_id' => $dvRequestMopId,
                        'bank_name' => ['BDO', 'BPI', 'Metrobank', 'Landbank'][array_rand(['BDO', 'BPI', 'Metrobank', 'Landbank'])],
                        'bank_accnum' => (string) rand(100000000, 999999999),
                        'bank_address' => 'Davao City Branch',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                $this->command->error("Error creating payment records: " . $e->getMessage());
                throw $e;
            }
            
            // Create logs based on status
            $this->createStatusLogs($dvRequest, $focal, $status, $createdAt);
            
            $createdCount++;
            
            // Progress indicator
            if ($createdCount % 20 === 0) {
                sleep(1);
                $this->command->info("Created {$createdCount} requests...");
            }
        }
        
        // Count statistics
        $totalCreated = DvRequest::whereBetween('created_at', [
            Carbon::create(2026, 1, 1, 0, 0, 0), 
            Carbon::create(2026, 2, 28, 23, 59, 59)
        ])->count();
        
        $janCount = DvRequest::whereBetween('created_at', [
            Carbon::create(2026, 1, 1, 0, 0, 0), 
            Carbon::create(2026, 1, 31, 23, 59, 59)
        ])->count();
        
        $febCount = DvRequest::whereBetween('created_at', [
            Carbon::create(2026, 2, 1, 0, 0, 0), 
            Carbon::create(2026, 2, 28, 23, 59, 59)
        ])->count();
        
        $exceedingCount = DvRequest::whereBetween('created_at', [
                Carbon::create(2026, 1, 1, 0, 0, 0), 
                Carbon::create(2026, 2, 28, 23, 59, 59)
            ])
            ->where('created_at', '<=', Carbon::now()->subDays(10))
            ->count();
            
        $this->command->info('✓ Created ' . $totalCreated . ' DV requests for Jan-Feb 2026');
        $this->command->info('   📅 January: ' . $janCount . ' requests');
        $this->command->info('   📅 February: ' . $febCount . ' requests');
        $this->command->info('   📊 Status distribution:');
        foreach ($statuses as $status) {
            $count = DvRequest::where('status', $status)
                ->whereBetween('created_at', [Carbon::create(2026, 1, 1), Carbon::create(2026, 2, 28, 23, 59, 59)])
                ->count();
            $this->command->info('      - ' . $status . ': ' . $count);
        }
        $this->command->info('   📊 ' . $exceedingCount . ' of these are exceeding the 10-day SLA threshold');
    }
    
    /**
     * Create status logs for DV Request
     */
    private function createStatusLogs($dvRequest, $focal, $status, $createdAt): void
    {
        // Always create submission log
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => $focal->id,
            'accountingunit_id' => null,
            'action' => 'Submitted',
            'remarks' => 'DV Request submitted by Focal Person',
            'created_at' => $createdAt,
        ]);
        
        $logTime = $createdAt->copy()->addHours(rand(2, 8));
        
        // Create logs based on status progression
        switch($status) {
            case 'IMSD_received':
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1, // Assuming IMSD unit ID
                    'action' => 'IMSD Received',
                    'remarks' => 'Request received by IMSD',
                    'created_at' => $logTime,
                ]);
                break;
                
            case 'IMSD_in_process':
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Received',
                    'remarks' => 'Request received by IMSD',
                    'created_at' => $logTime,
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD In Process',
                    'remarks' => 'Request is being processed by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(2, 12)),
                ]);
                break;
                
            case 'IMSD_verified':
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Received',
                    'remarks' => 'Request received by IMSD',
                    'created_at' => $logTime,
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD In Process',
                    'remarks' => 'Request is being processed by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(2, 12)),
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Verified',
                    'remarks' => 'Request verified by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(4, 24)),
                ]);
                break;
                
            case 'Incoming-GIP_verified':
            case 'Incoming-TUPAD_verified':
            case 'Incoming-TEV_verified':
                // First IMSD verification
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Received',
                    'remarks' => 'Request received by IMSD',
                    'created_at' => $logTime,
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD In Process',
                    'remarks' => 'Request is being processed by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(2, 12)),
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Verified',
                    'remarks' => 'Request verified by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(4, 24)),
                ]);
                
                // Then incoming verification
                $program = str_replace(['Incoming-', '_verified'], '', $status);
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 2, // Assuming Incoming unit ID
                    'action' => $status,
                    'remarks' => "Request verified by Incoming for {$program}",
                    'created_at' => $logTime->copy()->addHours(rand(8, 24)),
                ]);
                break;
                
            case 'ORS_obligated':
                // IMSD verification
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Received',
                    'remarks' => 'Request received by IMSD',
                    'created_at' => $logTime,
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD In Process',
                    'remarks' => 'Request is being processed by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(2, 12)),
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 1,
                    'action' => 'IMSD Verified',
                    'remarks' => 'Request verified by IMSD',
                    'created_at' => $logTime->copy()->addHours(rand(4, 24)),
                ]);
                
                // Incoming verification (random program)
                $programs = ['GIP', 'TUPAD', 'TEV'];
                $program = $programs[array_rand($programs)];
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 2,
                    'action' => "Incoming-{$program}_verified",
                    'remarks' => "Request verified by Incoming for {$program}",
                    'created_at' => $logTime->copy()->addHours(rand(8, 24)),
                ]);
                
                // ORS obligated
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 3, // Assuming Budget/ORS unit ID
                    'action' => 'ORS Obligated',
                    'remarks' => 'ORS obligated for this request',
                    'created_at' => $logTime->copy()->addHours(rand(16, 36)),
                ]);
                break;
                
            case 'Outgoing_finalized':
                // All previous steps plus outgoing
                $this->createLogsUpToORS($dvRequest, $logTime);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 4, // Assuming Outgoing unit ID
                    'action' => 'Outgoing Finalized',
                    'remarks' => 'Request finalized for outgoing',
                    'created_at' => $logTime->copy()->addHours(rand(24, 48)),
                ]);
                break;
                
            case 'RANCA_validated':
                // All previous steps plus RANCA
                $this->createLogsUpToORS($dvRequest, $logTime);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 4,
                    'action' => 'Outgoing Finalized',
                    'remarks' => 'Request finalized for outgoing',
                    'created_at' => $logTime->copy()->addHours(rand(24, 48)),
                ]);
                
                DvRequestLog::create([
                    'dv_request_id' => $dvRequest->id,
                    'focalperson_id' => null,
                    'accountingunit_id' => 5, // Assuming RANCA unit ID
                    'action' => 'RANCA Validated',
                    'remarks' => 'Request validated by RANCA',
                    'created_at' => $logTime->copy()->addHours(rand(36, 72)),
                ]);
                break;
        }
    }
    
    /**
     * Helper to create logs up to ORS obligated
     */
    private function createLogsUpToORS($dvRequest, $logTime): void
    {
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => null,
            'accountingunit_id' => 1,
            'action' => 'IMSD Received',
            'remarks' => 'Request received by IMSD',
            'created_at' => $logTime,
        ]);
        
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => null,
            'accountingunit_id' => 1,
            'action' => 'IMSD In Process',
            'remarks' => 'Request is being processed by IMSD',
            'created_at' => $logTime->copy()->addHours(rand(2, 12)),
        ]);
        
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => null,
            'accountingunit_id' => 1,
            'action' => 'IMSD Verified',
            'remarks' => 'Request verified by IMSD',
            'created_at' => $logTime->copy()->addHours(rand(4, 24)),
        ]);
        
        $programs = ['GIP', 'TUPAD', 'TEV'];
        $program = $programs[array_rand($programs)];
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => null,
            'accountingunit_id' => 2,
            'action' => "Incoming-{$program}_verified",
            'remarks' => "Request verified by Incoming for {$program}",
            'created_at' => $logTime->copy()->addHours(rand(8, 24)),
        ]);
        
        DvRequestLog::create([
            'dv_request_id' => $dvRequest->id,
            'focalperson_id' => null,
            'accountingunit_id' => 3,
            'action' => 'ORS Obligated',
            'remarks' => 'ORS obligated for this request',
            'created_at' => $logTime->copy()->addHours(rand(16, 36)),
        ]);
    }
}