<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olt;
use App\Models\OltProfile;
use App\Helpers\Olt\OltFactory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Exception;

class OltProfileController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:olts.view',   only: ['index', 'sync']),
            new Middleware('permission:olts.edit',   only: ['store', 'destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * List TCONT + Traffic profiles for an OLT (from DB).
     */
    public function index(Olt $olt)
    {
        $tcontProfiles  = OltProfile::where('olt_id', $olt->id)->tcontProfiles()->orderBy('name')->get();
        $trafficProfiles = OltProfile::where('olt_id', $olt->id)->trafficProfiles()->orderBy('name')->get();

        return view('admin.olts.profiles', compact('olt', 'tcontProfiles', 'trafficProfiles'));
    }

    /**
     * Sync profiles from OLT CLI to DB.
     * GET /{olt}/profiles/sync  (returns JSON for AJAX)
     */
    public function sync(Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);

            if (!method_exists($helper, 'getTcontProfiles')) {
                return response()->json([
                    'success' => false,
                    'message' => 'OLT brand ' . $olt->brand . ' belum mendukung pembacaan profile via CLI',
                ]);
            }

            $tcontData  = $helper->getTcontProfiles();
            $trafficData = $helper->getTrafficProfiles();

            DB::beginTransaction();

            // Sync TCONT profiles
            foreach ($tcontData as $p) {
                OltProfile::updateOrCreate(
                    ['olt_id' => $olt->id, 'type' => OltProfile::TYPE_TCONT, 'name' => $p['name']],
                    [
                        'config'      => ['type' => $p['type'], 'fbw' => $p['fbw'], 'abw' => $p['abw'], 'mbw' => $p['mbw']],
                        'description' => "Type {$p['type']} | FBW:{$p['fbw']} ABW:{$p['abw']} MBW:{$p['mbw']} kbps",
                    ]
                );
            }

            // Sync Traffic profiles
            foreach ($trafficData as $p) {
                OltProfile::updateOrCreate(
                    ['olt_id' => $olt->id, 'type' => OltProfile::TYPE_TRAFFIC, 'name' => $p['name']],
                    [
                        'config'      => ['sir' => $p['sir'], 'pir' => $p['pir']],
                        'description' => "SIR:{$p['sir']} PIR:{$p['pir']} kbps",
                    ]
                );
            }

            DB::commit();

            $this->activityLog->log('olt_profiles', "Synced profiles for OLT: {$olt->name}");

            return response()->json([
                'success' => true,
                'message' => 'Berhasil sync ' . count($tcontData) . ' TCONT + ' . count($trafficData) . ' Traffic profile dari OLT',
                'tcont_count'   => count($tcontData),
                'traffic_count' => count($trafficData),
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create a profile (push to OLT + save DB).
     * POST /{olt}/profiles
     */
    public function store(Request $request, Olt $olt)
    {
        $profileType = $request->input('profile_type'); // 'tcont' or 'traffic'

        if ($profileType === OltProfile::TYPE_TCONT) {
            $request->validate([
                'name'     => 'required|string|max:64|regex:/^[A-Za-z0-9._\-]+$/',
                'dba_type' => 'required|integer|in:1,2,3,4,5',
                'fbw'      => 'nullable|integer|min:0|max:9953280',
                'abw'      => 'nullable|integer|min:0|max:9953280',
                'mbw'      => 'nullable|integer|min:0|max:9953280',
            ]);

            $name    = $request->name;
            $dbaType = (int) $request->dba_type;
            $fbw     = (int) ($request->fbw ?? 0);
            $abw     = (int) ($request->abw ?? 0);
            $mbw     = (int) ($request->mbw ?? 0);

            try {
                $helper = OltFactory::make($olt);
                $res = $helper->createTcontProfile($name, $dbaType, $fbw, $abw, $mbw);

                if (!$res['success']) {
                    return back()->with('error', $res['message']);
                }

                OltProfile::create([
                    'olt_id'      => $olt->id,
                    'type'        => OltProfile::TYPE_TCONT,
                    'name'        => $name,
                    'config'      => ['type' => $dbaType, 'fbw' => $fbw, 'abw' => $abw, 'mbw' => $mbw],
                    'description' => "Type {$dbaType} | FBW:{$fbw} ABW:{$abw} MBW:{$mbw} kbps",
                ]);

                $this->activityLog->log('olt_profiles', "Created TCONT profile '{$name}' on OLT: {$olt->name}");
                return back()->with('success', "TCONT profile '{$name}' berhasil dibuat di OLT");

            } catch (Exception $e) {
                return back()->with('error', 'Error: ' . $e->getMessage());
            }
        }

        if ($profileType === OltProfile::TYPE_TRAFFIC) {
            $request->validate([
                'name' => 'required|string|max:64|regex:/^[A-Za-z0-9._\-]+$/',
                'sir'  => 'required|integer|min:1|max:9953280',
                'pir'  => 'required|integer|min:1|max:9953280',
            ]);

            $name = $request->name;
            $sir  = (int) $request->sir;
            $pir  = (int) $request->pir;

            try {
                $helper = OltFactory::make($olt);
                $res = $helper->createTrafficProfile($name, $sir, $pir);

                if (!$res['success']) {
                    return back()->with('error', $res['message']);
                }

                OltProfile::create([
                    'olt_id'      => $olt->id,
                    'type'        => OltProfile::TYPE_TRAFFIC,
                    'name'        => $name,
                    'config'      => ['sir' => $sir, 'pir' => $pir],
                    'description' => "SIR:{$sir} PIR:{$pir} kbps",
                ]);

                $this->activityLog->log('olt_profiles', "Created Traffic profile '{$name}' on OLT: {$olt->name}");
                return back()->with('success', "Traffic profile '{$name}' berhasil dibuat di OLT");

            } catch (Exception $e) {
                return back()->with('error', 'Error: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Tipe profile tidak valid');
    }

    /**
     * Delete a profile (remove from OLT + delete from DB).
     * DELETE /{olt}/profiles/{profile}
     */
    public function destroy(Olt $olt, OltProfile $profile)
    {
        if ($profile->olt_id !== $olt->id) {
            abort(403);
        }

        try {
            $helper = OltFactory::make($olt);

            if ($profile->type === OltProfile::TYPE_TCONT) {
                $res = $helper->deleteTcontProfile($profile->name);
            } elseif ($profile->type === OltProfile::TYPE_TRAFFIC) {
                $res = $helper->deleteTrafficProfile($profile->name);
            } else {
                return response()->json(['success' => false, 'message' => 'Tipe profile tidak didukung untuk delete via CLI']);
            }

            if (!$res['success']) {
                return response()->json(['success' => false, 'message' => $res['message']]);
            }

            $name = $profile->name;
            $profile->delete();

            $this->activityLog->log('olt_profiles', "Deleted profile '{$name}' from OLT: {$olt->name}");

            return response()->json(['success' => true, 'message' => "Profile '{$name}' berhasil dihapus"]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
