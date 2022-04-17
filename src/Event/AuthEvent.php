<?php

namespace App\Event;

use App\Event\AbstractEvent;

class AuthEvent extends AbstractEvent
{
    # AUTHENTICATION #
    const AUTHENTICATION_SUCCESS = 'authentication_success';
    const AUTHENTICATION_FAILURE = 'authentication_failure';
    # REFRESH_TOKEN #
    const REFRESH_TOKEN_SUCCESS = 'refresh_token_success';
    const REFRESH_TOKEN_FAILURE = 'refresh_token_failure';
    # LOGOUT #
    const LOGOUT_SUCCESS = 'logout_success';
    const LOGOUT_FAILURE = 'logout_failure';
    # REGISTERED #
    const REGISTERED_SUCCESS = 'registered_success';
    const REGISTERED_FAILURE = 'registered_failure';
    # EMAIL_VERIFY #
    const EMAIL_VERIFY_EMAIL_SENDED_SUCCESS = 'email_verify_email_sended_success';
    const EMAIL_VERIFY_EMAIL_SENDED_FAILURE = 'email_verify_email_sended_failure';
    const EMAIL_VERIFY_SUCCESS = 'email_verify_success';
    const EMAIL_VERIFY_FAILURE = 'email_verify_failure';
    # RESET_PASSWORD #
    const RESET_PASSWORD_EMAIL_SENDED_SUCCESS = 'reset_password_email_sended_success';
    const RESET_PASSWORD_EMAIL_SENDED_FAILURE = 'reset_password_email_sended_failure';
    const RESET_PASSWORD_CHECK_TOKEN_SUCCESS = 'reset_password_check_token_success';
    const RESET_PASSWORD_CHECK_TOKEN_FAILURE = 'reset_password_check_token_failure';
    const RESET_PASSWORD_SUCCESS = 'reset_password_success';
    const RESET_PASSWORD_FAILURE = 'reset_password_failure';
}
