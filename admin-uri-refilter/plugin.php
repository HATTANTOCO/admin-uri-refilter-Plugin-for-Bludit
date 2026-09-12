<?php
/**
 * plugin.php
 *
 * Initialization, settings form management, and core file URL filter (ADMIN_URI_FILTER) auto-recovery class for the Admin URI Refilter plugin.
 *
 * @package    Admin URI Refilter
 * @version    1.0.0
 * @author     HATTA <https://hattantoco.com>
 * @license    MIT License
 */

class pluginAdminUriRefilter extends Plugin {

    // 1. Initialize the database (Tracks only the custom URL filter field)
    public function init()
    {
        $this->dbFields = array(
            'savedAdminUriFilter' => 'admin' 
        );
    }

    // 2. Method called on plugin settings on the admin area
    public function form()
    {
        global $L;

        // Display plugin description
        $html = '<p class="alert alert-primary">' . $this->description() . '</p>';

        // [Feature C] MANUAL REWRITE AUTO-SYNC
        $dbFilter = $this->getValue('savedAdminUriFilter');
        if (ADMIN_URI_FILTER !== 'admin' && ADMIN_URI_FILTER !== $dbFilter) {
            $this->db['savedAdminUriFilter'] = ADMIN_URI_FILTER;
            $this->save();
            $dbFilter = ADMIN_URI_FILTER;
        }

        // [Fastest Layout Placement]
        $btnUrl = Session::get('uri_refilter_btn_url');
        if ($btnUrl) {
            Session::remove('uri_refilter_btn_url');
            $safeBtnUrl = sanitize::html($btnUrl);

            $html .= '<div class="alert alert-info mt-3 mb-4">';
            $html .= '<p class="mb-2"><b>' . $L->get('core-rewrite-redirect-prompt') . '</b></p>';
            $html .= '<a href="' . $safeBtnUrl . '" class="btn btn-primary btn-sm">' . $L->get('core-rewrite-redirect-btn') . '</a>';
            $html .= '</div>';
        }

        $html .= '<h4 class="mt-4">' . $L->get('admin-url-filter') . ' (' . $L->get('core-file-rewrite') . ')</h4>';
        $file_path = PATH_BOOT . 'variables.php';
        
        // Check file write permissions
        if (!is_writable($file_path)) {
            $html .= '<p class="alert alert-danger"><code>' . $L->get('file-not-writable-warning') . '</code> <code>' . $file_path . '</code></p>';
        }

        // Priority check for the display value (Session > DB > Constant)
        $pendingFilter = Session::get('pending_admin_uri_filter');
        
        if (!empty($pendingFilter)) {
            $displayValue = $pendingFilter;
        } elseif (!empty($dbFilter)) {
            $displayValue = $dbFilter;
        } else {
            $displayValue = ADMIN_URI_FILTER;
        }

        // Input Form
        $html .= '<div>';
        $html .= '<label>' . $L->get('admin-uri-filter-constant') . '</label>';
        $html .= '<input name="adminUriFilter" type="text" class="form-control" value="' . sanitize::html($displayValue) . '" pattern="^[a-zA-Z0-9_-]+$" title="Alphanumeric characters, hyphens, and underscores only." required>';
        $html .= '</div>';

        return $html;
    }

    // 3. Method called when the user clicks on the Save button
    public function post()
    {
        $newFilter = isset($_POST['adminUriFilter']) ? trim($_POST['adminUriFilter']) : '';

        // Validation check
        if (!empty($newFilter) && preg_match('/^[a-zA-Z0-9_-]+$/', $newFilter)) {
            if ($newFilter !== ADMIN_URI_FILTER) {
                Session::set('pending_admin_uri_filter', $newFilter);

                $newAdminUrl = HTML_PATH_ROOT . $newFilter . '/configure-plugin/' . __CLASS__;
                Session::set('uri_refilter_btn_url', $newAdminUrl);
            }
            $this->db['savedAdminUriFilter'] = $newFilter;
        }

        return parent::post();
    }

    // 4. Hook executed after the admin area has loaded
    public function afterAdminLoad()
    {
        global $L;

        $login = new Login();
        if ($login->isLogged()) {
            
            // ==========================================
            // [Feature A] AUTOMATIC RECOVERY
            // ==========================================
            $savedFilter = $this->getValue('savedAdminUriFilter');
            
            if (ADMIN_URI_FILTER === 'admin' && !empty($savedFilter) && $savedFilter !== 'admin') {
                $file_path = PATH_BOOT . 'variables.php';

                if (is_writable($file_path)) {

                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $savedFilter)) {
                        
                        $content = file_get_contents($file_path);
                        $pattern = "/define\s*\(\s*['\"]ADMIN_URI_FILTER['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/i";
                        $replacement = "define('ADMIN_URI_FILTER', '" . $savedFilter . "');";
                        $new_content = preg_replace($pattern, $replacement, $content);

                        // Validate file rewrite content before writing
                        if ($new_content !== null) {
                            $writeSuccess = file_put_contents($file_path, $new_content, LOCK_EX);
                            
                            if ($writeSuccess !== false) {
                                // Set auto-recovery alert for the next page load
                                Alert::set(sprintf($L->get('core-reset-auto-recovered-alert'), $savedFilter));
                                
                                // Get absolute path for the recovered dashboard
                                $recoveredDashboard = HTML_PATH_ROOT . $savedFilter . '/dashboard';
                                
                                // Execute stealth redirection via client-side JavaScript replace
                                echo '<script>';
                                echo 'window.location.replace("' . sanitize::html($recoveredDashboard) . '");';
                                echo '</script>';
                                exit; // Stop further 404 rendering processes
                            } else {
                                // Infinite loop prevention safety trigger
                                Alert::set('Plugin Error: [Admin URI Refilter] Failed to write to variables.php. Auto-recovery aborted to prevent loop.', Log::TYPE_ERROR);
                                return;
                            }
                        }
                    } else {
                        Alert::set('Plugin Error: [Admin URI Refilter] Invalid characters detected in savedAdminUriFilter. Auto-recovery aborted.', Log::TYPE_ERROR);
                        return;
                    }
                }
            }

            // ==========================================
            // [Feature B] MANUAL SAVE REWRITE
            // ==========================================
            $pendingFilter = Session::get('pending_admin_uri_filter');

            if ($pendingFilter) {
                $file_path = PATH_BOOT . 'variables.php';

                if (is_writable($file_path)) {
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $pendingFilter)) {
                        $content = file_get_contents($file_path);
                        $pattern = "/define\s*\(\s*['\"]ADMIN_URI_FILTER['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/i";
                        $replacement = "define('ADMIN_URI_FILTER', '" . $pendingFilter . "');";
                        $new_content = preg_replace($pattern, $replacement, $content);

                        if ($new_content !== null) {
                            file_put_contents($file_path, $new_content, LOCK_EX);
                        }
                    } else {
                        Session::remove('pending_admin_uri_filter');
                        Alert::set('Plugin Error: [Admin URI Refilter] Invalid characters detected in pending_admin_uri_filter. Rewrite aborted.', Log::TYPE_ERROR);
                        return;
                    }
                }
                Session::remove('pending_admin_uri_filter');
            }
        }
    }
}
?>
