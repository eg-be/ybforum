<?php

/**
 * Copyright 2017 Elias Gerber <eg@zame.ch>
 * 
 * This file is part of YbForum1898.
 *
 * YbForum1898 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * YbForum1898 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with YbForum1898.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * The configuration for re-captcha v3. Do not commit the secret, stupid!
 * The admin-console is here: https://www.google.com/recaptcha/admin
 */
final class CaptchaV3Config
{
    /**
     * @var bool Use captcha verification while registering new users.
     */
    const CAPTCHA_VERIFY = true;

    /**
     * @var number Minimal required score of the captcha-process (0.0 - 1.0), 0.0 is bad, 1.0 is good
     */
    const MIN_REQUIRED_SCORE = 0.5;

    /**
     * @var string Site-key, from the admin-console
     */
    const CAPTCHA_SITE_KEY = '';

    /**
     * @var string Secret-key, from the admin-console
     */
    const CAPTCHA_SECRET = '';

    /**
     * @var string Action name for registering user action
     */
    const CAPTCHA_REGISTER_USER_ACTION = 'register';
}
