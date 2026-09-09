<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminTab;
use VanillaCms\Auth\Auth;
use VanillaCms\Auth\AuthException;
use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Router\Router;

class AccountTab extends AdminTab
{
    public function __construct()
    {
        parent::__construct('account', 'Account');
    }

    public static function getLogoutApiUrl(): string
    {
        return "/admin/account/logout";
    }

    public static function getPasswordChangeApiUrl(): string
    {
        return "/admin/account/password-change";
    }

    public static function getEmailChangeApiUrl(): string
    {
        return "/admin/account/email-change";
    }

    public static function get2FAEnableApiUrl(): string
    {
        return "/admin/account/2fa/enable";
    }

    public static function get2FAConfirmOtpApiUrl(): string
    {
        return "/admin/account/2fa/confirm-otp";
    }

    public static function get2FADisableApiUrl(): string
    {
        return "/admin/account/2fa/disable";
    }

    public function handleApiRequest(array $segments): bool
    {
        if (!AdminController::isVerifiedPost()) {
            return false;
        }

        if ($segments === ['logout']) {
            Auth::logout();
            Router::redirect(Auth::unauthorizedUrl());
        }
        if ($segments === ['password-change']) {
            $this->respondToAction(fn () => Auth::changePassword(
                (string)($_POST['old-password'] ?? ''),
                (string)($_POST['new-password'] ?? '')
            ), 'Password aggiornata.');
            return true;
        }
        if ($segments === ['email-change']) {
            $this->respondToAction(fn () => Auth::changeEmail(
                trim((string)($_POST['new-mail'] ?? '')),
                (string)($_POST['password'] ?? '')
            ), "Ti abbiamo inviato un'email di conferma al nuovo indirizzo.");
            return true;
        }
        if ($segments === ['2fa', 'enable']) {
            $this->respondToAction(fn () => Auth::enable2FA((string)($_POST['password'] ?? '')));
            return true;
        }
        if ($segments === ['2fa', 'confirm-otp']) {
            $this->respondToAction(fn () => Auth::confirm2FA((string)($_POST['verification-code'] ?? '')));
            return true;
        }
        if ($segments === ['2fa', 'disable']) {
            $this->respondToAction(fn () => Auth::disable2FA());
            return true;
        }
        return false;
    }

    /** Runs an Auth action and writes the uniform {success, message} JSON contract admin.js expects. */
    private function respondToAction(callable $action, ?string $successMessage = null): void
    {
        header('Content-Type: application/json');
        try {
            $action();
            echo json_encode(['success' => true, 'message' => $successMessage]);
        } catch (AuthException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function dispatch(array $segments): void
    {
        $this->renderAccountOptions();
    }

    protected function renderAccountOptions(): void
    {
        ?>
        <div class="vcms-page-header">
            <h1 class="vcms-page-title">Your Account</h1>
            <div class="vcms-upload-editor__nav">
                <span class="vcms-upload-editor__nav__item">
                    <?= htmlspecialchars(Auth::getUsername()) ?>
                </span>
                <form method="post" action="<?= htmlspecialchars(self::getLogoutApiUrl()) ?>" class="vcms-form"
                      data-confirm="Are you sure you want to logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="vcms-btn vcms-btn--danger">Logout</button>
                </form>
            </div>
        </div>

        <div class="vcms-fields-container">
            <form method="post" action="<?= htmlspecialchars(self::getPasswordChangeApiUrl()) ?>" class="vcms-form" data-vcms-ajax>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <div class="vcms-field vcms-field--composite">
                    <div class="vcms-field__label">
                        Change Password
                        <div class="vcms-field__group">
                            <div class="vcms-field vcms-field--text">
                                <label class="vcms-field__label">
                                    Old Password
                                    <input class="vcms-field__input" type="password" name="<?= "old-password" ?>" value="">
                                </label>
                            </div>
                            <div class="vcms-field vcms-field--text">
                                <label class="vcms-field__label">
                                    New Password
                                    <input class="vcms-field__input" type="password" name="<?= "new-password" ?>" value="">
                                </label>
                            </div>
                            <span data-vcms-form-message></span>
                        </div>
                        <button type="submit" class="vcms-btn vcms-btn--primary">
                            <span class="vcms-btn__spinner vcms-icon--spin"><?php vcms_icon('spinner') ?></span>
                            Update password
                        </button>
                    </div>
                </div>
            </form>

            <form method="post" action="<?= htmlspecialchars(self::getEmailChangeApiUrl()) ?>" class="vcms-form" data-vcms-ajax>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <div class="vcms-field vcms-field--composite">
                    <div class="vcms-field__label">
                        Change e-mail
                        <div class="vcms-field__group">
                            <div class="vcms-field vcms-field--text">
                                <label class="vcms-field__label">
                                    New email
                                    <input class="vcms-field__input" type="email" name="<?= "new-mail" ?>" value="">
                                </label>
                            </div>
                            <div class="vcms-field vcms-field--text">
                                <label class="vcms-field__label">
                                    Password
                                    <input class="vcms-field__input" type="password" name="<?= "password" ?>" value="">
                                </label>
                            </div>
                            <span data-vcms-form-message></span>
                        </div>
                        <button type="submit" class="vcms-btn vcms-btn--primary">
                            <span class="vcms-btn__spinner vcms-icon--spin"><?php vcms_icon('spinner') ?></span>
                            Update mail
                        </button>
                    </div>
                </div>
            </form>


            <?php if (!Auth::has2FA()): ?>
                <form method="post" action="<?= htmlspecialchars(self::get2FAEnableApiUrl()) ?>" class="vcms-form"
                      data-vcms-swap-for="two-factor-auth-otp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="vcms-field vcms-field--composite">
                        <div class="vcms-field__label">
                            Two-factor authentication
                            <div class="vcms-field__group">
                                <div class="vcms-field vcms-field--text">
                                    <label class="vcms-field__label">
                                        Password
                                        <input class="vcms-field__input" type="password" name="<?= "password" ?>" value="">
                                    </label>
                                </div>
                                <span data-vcms-form-message></span>
                            </div>
                            <button type="submit" class="vcms-btn vcms-btn--primary">
                                <span class="vcms-btn__spinner vcms-icon--spin"><?php vcms_icon('spinner') ?></span>
                                Request code
                            </button>
                        </div>
                    </div>
                </form>

                <form method="post" action="<?= htmlspecialchars(self::get2FAConfirmOtpApiUrl()) ?>" class="vcms-form" id="two-factor-auth-otp-form" hidden data-vcms-ajax data-vcms-reload-on-success>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="vcms-field vcms-field--composite">
                        <div class="vcms-field__label">
                            Two-factor authentication
                            <div class="vcms-field__group">
                                <div class="vcms-field vcms-field--text">
                                    <label class="vcms-field__label">
                                        Verification code
                                        <input class="vcms-field__input" type="text" name="<?= "verification-code" ?>" value="">
                                    </label>
                                </div>
                                <span data-vcms-form-message></span>
                            </div>
                            <button type="submit" class="vcms-btn vcms-btn--primary">
                                <span class="vcms-btn__spinner vcms-icon--spin"><?php vcms_icon('spinner') ?></span>
                                Submit code
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="vcms-field__label">
                    Two-factor authentication
                    <form method="post" action="<?= htmlspecialchars(self::get2FADisableApiUrl()) ?>" class="vcms-form"
                          data-confirm="Are you sure you want to disable 2FA?" data-vcms-ajax data-vcms-reload-on-success>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <span data-vcms-form-message></span>
                        <button type="submit" class="vcms-btn vcms-btn--danger">
                            <span class="vcms-btn__spinner vcms-icon--spin"><?php vcms_icon('spinner') ?></span>
                            Disable 2FA
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}