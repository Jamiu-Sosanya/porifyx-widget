<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    protected InstallerService $installer;

    public function __construct(InstallerService $installer)
    {
        $this->installer = $installer;
    }

    /**
     * Show installer welcome page (Step 1)
     */
    public function welcome()
    {
        return view('installer.welcome');
    }

    /**
     * Check server requirements (Step 2)
     */
    public function requirements()
    {
        $requirements = $this->installer->checkServerRequirements();

        return view('installer.requirements', [
            'requirements' => $requirements,
            'allPassed'    => collect($requirements)->every(fn ($req) => $req['status']),
        ]);
    }

    /**
     * Check file permissions (Step 3)
     */
    public function permissions()
    {
        $permissions = $this->installer->checkFilePermissions();

        return view('installer.permissions', [
            'permissions' => $permissions,
            'allPassed'   => collect($permissions)->every(fn ($perm) => $perm['writable']),
        ]);
    }

    /**
     * Show environment configuration (Step 4)
     */
    public function environment()
    {
        return view('installer.environment');
    }

    /**
     * Save environment configuration
     */
    public function saveEnvironment(Request $request)
    {
        $validated = $request->validate([
            'app_name'       => 'required|string|max:255',
            'app_url'        => 'required|url',
            'db_connection'  => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host'        => 'required|string',
            'db_port'        => 'required|numeric',
            'db_database'    => 'required|string',
            'db_username'    => 'required|string',
            'db_password'    => 'nullable|string',
        ]);

        try {
            $this->installer->createEnvironmentFile($validated);
            return redirect()->route('installer.database')->with('success', 'Environment configured successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show database migration page (Step 5)
     */
    public function database()
    {
        return view('installer.database');
    }

    /**
     * Run migrations and seeders
     */
    public function migrate(Request $request)
    {
        try {
            $this->installer->testDatabaseConnection();
            $this->installer->runMigrations();

            if ($request->boolean('seed_demo_data')) {
                $this->installer->seedDemoData();
            }

            return redirect()->route('installer.admin')->with('success', 'Database setup completed!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show admin user setup page (Step 6)
     */
    public function admin()
    {
        return view('installer.admin');
    }

    /**
     * Create admin user
     */
    public function createAdmin(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        try {
            $this->installer->createAdminUser($validated);
            return redirect()->route('installer.finish')->with('success', 'Admin user created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show finish page (Step 7)
     */
    public function finish()
    {
        // Mark installation as complete
        $this->installer->markInstallationComplete();

        return view('installer.finish');
    }
}
