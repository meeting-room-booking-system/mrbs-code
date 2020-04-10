<?php
namespace MRBS;



define('LDAP_SUCCESS',                        0x00);
define('LDAP_OPERATIONS_ERROR',               0x01);
define('LDAP_PROTOCOL_ERROR',                 0x02);
define('LDAP_TIMELIMIT_EXCEEDED',             0x03);
define('LDAP_SIZELIMIT_EXCEEDED',             0x04);
define('LDAP_COMPARE_FALSE',                  0x05);
define('LDAP_COMPARE_TRUE',                   0x06);
define('LDAP_AUTH_METHOD_NOT_SUPPORTED',      0x07);
define('LDAP_STRONG_AUTH_REQUIRED',           0x08);
// Not used in LDAPv3
define('LDAP_PARTIAL_RESULTS',                0x09);

// Next 5 new in LDAPv3
define('LDAP_REFERRAL',                       0x0a);
define('LDAP_ADMINLIMIT_EXCEEDED',            0x0b);
define('LDAP_UNAVAILABLE_CRITICAL_EXTENSION', 0x0c);
define('LDAP_CONFIDENTIALITY_REQUIRED',       0x0d);
define('LDAP_SASL_BIND_INPROGRESS',           0x0e);

define('LDAP_NO_SUCH_ATTRIBUTE',              0x10);
define('LDAP_UNDEFINED_TYPE',                 0x11);
define('LDAP_INAPPROPRIATE_MATCHING',         0x12);
define('LDAP_CONSTRAINT_VIOLATION',           0x13);
define('LDAP_TYPE_OR_VALUE_EXISTS',           0x14);
define('LDAP_INVALID_SYNTAX',                 0x15);

define('LDAP_NO_SUCH_OBJECT',                 0x20);
define('LDAP_ALIAS_PROBLEM',                  0x21);
define('LDAP_INVALID_DN_SYNTAX',              0x22);
// Next two not used in LDAPv3',
define('LDAP_IS_LEAF',                        0x23);
define('LDAP_ALIAS_DEREF_PROBLEM',            0x24);

define('LDAP_INAPPROPRIATE_AUTH',             0x30);
define('LDAP_INVALID_CREDENTIALS',            0x31);
define('LDAP_INSUFFICIENT_ACCESS',            0x32);
define('LDAP_BUSY',                           0x33);
define('LDAP_UNAVAILABLE',                    0x34);
define('LDAP_UNWILLING_TO_PERFORM',           0x35);
define('LDAP_LOOP_DETECT',                    0x36);

define('LDAP_SORT_CONTROL_MISSING',           0x3C);
define('LDAP_INDEX_RANGE_ERROR',              0x3D);

define('LDAP_NAMING_VIOLATION',               0x40);
define('LDAP_OBJECT_CLASS_VIOLATION',         0x41);
define('LDAP_NOT_ALLOWED_ON_NONLEAF',         0x42);
define('LDAP_NOT_ALLOWED_ON_RDN',             0x43);
define('LDAP_ALREADY_EXISTS',                 0x44);
define('LDAP_NO_OBJECT_CLASS_MODS',           0x45);
define('LDAP_RESULTS_TOO_LARGE',              0x46);
// Next two for LDAPv3
define('LDAP_AFFECTS_MULTIPLE_DSAS',          0x47);
define('LDAP_OTHER',                          0x50);

// Used by some APIs
define('LDAP_SERVER_DOWN',                    0x51);
define('LDAP_LOCAL_ERROR',                    0x52);
define('LDAP_ENCODING_ERROR',                 0x53);
define('LDAP_DECODING_ERROR',                 0x54);
define('LDAP_TIMEOUT',                        0x55);
define('LDAP_AUTH_UNKNOWN',                   0x56);
define('LDAP_FILTER_ERROR',                   0x57);
define('LDAP_USER_CANCELLED',                 0x58);
define('LDAP_PARAM_ERROR',                    0x59);
define('LDAP_NO_MEMORY',                      0x5a);

// Preliminary LDAPv3 codes
define('LDAP_CONNECT_ERROR',                  0x5b);
define('LDAP_NOT_SUPPORTED',                  0x5c);
define('LDAP_CONTROL_NOT_FOUND',              0x5d);
define('LDAP_NO_RESULTS_RETURNED',            0x5e);
define('LDAP_MORE_RESULTS_TO_RETURN',         0x5f);
define('LDAP_CLIENT_LOOP',                    0x60);
define('LDAP_REFERRAL_LIMIT_EXCEEDED',        0x61);



