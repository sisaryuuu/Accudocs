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
            
            // Create DV Requests with only submitted status
            $this->createSubmittedDvRequests($focalPersons, $programs, $respoCenters, $modeOfPayments);
            
            DB::commit();
            
            $this->command->info('✅ DV Request seeder completed successfully! Only "submitted" status created.');
            
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
     * Create DV Requests with only submitted status
     */
    private function createSubmittedDvRequests($focalPersons, $programs, $respoCenters, $modeOfPayments): void
    {
        $this->command->info('Creating DV Requests for December 2025 (submitted status only)...');
        
        $particularsTemplates = [
            'Payment for training materials and supplies',
            'Wages for TUPAD beneficiaries - Phase 1',
            'Procurement of office equipment',
            'Professional fees for consultancy services',
            'Travel expenses for monitoring activities',
            'Food and accommodation for training participants',
            'Honorarium for resource speakers',
            'Internet and communication expenses',
            'Year-end bonuses for program coordinators',
            'Christmas party expenses for beneficiaries',
            '13th month pay for project-based employees',
            'Year-end incentive allowance',
            'Procurement of Christmas gift packages',
            'Year-end training and development seminar',
            'Annual program review and assessment costs',
            'Year-end reporting and documentation',
            'Holiday season operational expenses',
            'New Year preparation activities',
            'Annual maintenance of equipment',
            'Year-end financial audit services',
        ];
        
        // Total number of requests to create (100 for December)
        $totalRequests = 100;
        $userSequence = 200;
        $createdCount = 0;
        
        // Create 100 DV requests spread throughout December
        for ($i = 0; $i < $totalRequests; $i++) {
            $focal = $focalPersons[array_rand($focalPersons)];
            $program = $programs[array_rand($programs)];
            $respoCenter = $respoCenters[array_rand($respoCenters)];
            $mop = $modeOfPayments[array_rand($modeOfPayments)];
            
            // Generate reference number: DVORS-XXXXXXXX (8 random uppercase characters)
            do {
                $referenceNumber = 'DVORS-' . strtoupper(Str::random(8));
            } while (DvRequest::where('reference_number', $referenceNumber)->exists());
            
            // Generate dates within December 2026 (1st to 31st)
            $day = rand(1, 31);
            $createdAt = Carbon::create(2025, 12, $day, rand(8, 17), rand(0, 59));
            $updatedAt = $createdAt->copy()->addHours(rand(1, 5));
            
            // Generate Accounting Control Number (simple incrementing number)
            $accountingControlNumber = $userSequence;
            
            // STATUS IS ALWAYS "submitted"
            $status = 'submitted';
            
            // DV number should be null for submitted requests
            $dvNumber = null;
            
            // Set confirmation flags - all submitted requests should be confirmed
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
            
            // Create ONLY the initial submission log (no additional logs for other statuses)
            DvRequestLog::create([
                'dv_request_id' => $dvRequest->id,
                'focalperson_id' => $focal->id,
                'accountingunit_id' => null,
                'action' => 'Submitted',
                'remarks' => 'DV Request submitted by Focal Person',
                'created_at' => $createdAt,
            ]);
            
            $createdCount++;
            
            // Progress indicator
            if ($createdCount % 20 === 0) {
                sleep(1);
                $this->command->info("Created {$createdCount} submitted requests...");
            }
        }
        
        $this->command->info('✓ Created ' . $createdCount . ' DV requests with "submitted" status for December 2025');
    }
}