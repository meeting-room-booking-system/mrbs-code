<?php
declare(strict_types=1);
namespace MRBS\Session;


/**
 * Keep the matching auth/session scheme required by SAML while adding the
 * Keycloak directory lookup in AuthKeycloak. All SAML settings still apply.
 */
class SessionKeycloak extends SessionSaml
{
}
