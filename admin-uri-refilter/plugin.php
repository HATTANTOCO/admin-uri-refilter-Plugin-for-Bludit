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

    /**
     * Initialize the database (Tracks only the custom URL filter field)
     */
    public function init()
    {
        $this->dbFields = array(
            'savedAdminUriFilter' => 'admin' 
        );
    }

    /**
     * Method called on plugin settings on the admin area
     */
    public function form()
    {
        global $L;
        global $security;

        $html = '<p class="alert alert-primary">' . $this->description() . '</p>';

        // CSRF protection with custom token
        $html .= '<input type="hidden" name="tokenPlugin" value="' . $security->getTokenCSRF() . '">';

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
            if (ADMIN_URI_FILTER === $pendingFilter) {
                Session::remove('pending_admin_uri_filter');
                $displayValue = $pendingFilter;
            } else {
                $displayValue = $pendingFilter;
            }
        } elseif (!empty($dbFilter)) {
            $displayValue = $dbFilter;
        } else {
            $displayValue = ADMIN_URI_FILTER;
        }

        // Lock input field if redirect prompt is active
        $disabledAttr = '';
        if ($btnUrl) {
            $disabledAttr = ' readonly style="background-color: #e9ecef; cursor: not-allowed;"';
        }

        // Input Form
        $html .= '<div>';
        $html .= '<label>' . $L->get('admin-uri-filter-constant') . '</label>';
        $html .= '<input name="adminUriFilter" type="text" class="form-control" value="' . sanitize::html($displayValue) . '" pattern="^[a-zA-Z0-9_-]+$" title="Alphanumeric characters, hyphens, and underscores only." required' . $disabledAttr . '>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Method called when the user clicks on the Save button
     */
    public function post()
    {
        // CSRF Token validation
        if (isset($_POST['adminUriFilter'])) {
            global $security;

            if (!isset($_POST['tokenPlugin']) || !$security->validateTokenCSRF($_POST['tokenPlugin'])) {
                return false; 
            }
        }

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

    /**
     * Hook executed after the admin area has loaded
     */
    public function afterAdminLoad()
    {
        global $L;

        $login = new Login();
        
        // Restrict execution to logged-in users with administrator privileges
        if ($login->isLogged() && $login->role() === 'admin') {
            
            // Skip execution on Ajax background requests to prevent data loss or unexpected redirects
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return;
            }

            $file_path = PATH_BOOT . 'variables.php';

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
                                // Get absolute path for the recovered dashboard
                                $recoveredDashboard = HTML_PATH_ROOT . $savedFilter . '/dashboard';
                                echo '<script>';
                                echo 'window.location.replace("' . sanitize::html($recoveredDashboard) . '");';
                                echo '</script>';
                                exit; // Stop further 404 rendering processes
                            } else {
                                return;
                            }
                        }
                    } else {
                        return;
                    }
                }
            }

            // ==========================================
            // [Feature B] MANUAL SAVE REWRITE
            // ==========================================
            $pendingFilter = Session::get('pending_admin_uri_filter');

            if ($pendingFilter) {
                if (ADMIN_URI_FILTER === $pendingFilter) {
                    Session::remove('pending_admin_uri_filter');
                    return;
                }

                if (is_writable($file_path)) {
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $pendingFilter)) {
                        $content = file_get_contents($file_path);
                        $pattern = "/define\s*\(\s*['\"]ADMIN_URI_FILTER['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/i";
                        $replacement = "define('ADMIN_URI_FILTER', '" . $pendingFilter . "');";
                        $new_content = preg_replace($pattern, $replacement, $content);

                        if ($new_content !== null && $content !== $new_content) {
                            $tmp_file = $file_path . '.' . time() . mt_rand(1000, 9999) . '.tmp';
                            if (file_put_contents($tmp_file, $new_content, LOCK_EX) !== false) {
                                if (rename($tmp_file, $file_path)) {
                                    if (function_exists('opcache_invalidate')) {
                                        @opcache_invalidate($file_path, true);
                                    }
                                    
                                    // 💡 Feature B Success Notification
                                    // Displayed to the admin upon successful manual rewrite and migration to the new URL
                                    $msg = sprintf($L->get('core-rewrite-success-alert'), sanitize::html($pendingFilter));
                                    Alert::set($msg, Log::TYPE_INFO);
                                    
                                    Session::remove('pending_admin_uri_filter');
                                } else {
                                    @unlink($tmp_file);
                                    return;
                                }
                            }
                        }
                    } else {
                        Session::remove('pending_admin_uri_filter');
                        return;
                    }
                }
            }
        }
    }
}
?>
