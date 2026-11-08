<?php
/**
 * Starts the session (if not already started). Included by every
 * page and API endpoint so session data is always available.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
