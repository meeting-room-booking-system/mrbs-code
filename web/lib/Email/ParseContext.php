<?php

namespace Email;

/**
 * Per-parse mutable state for {@see Parse::parse()}.
 *
 * Holds the input snapshot and hoisted config, the state-machine control
 * variables, and the ~24-field address accumulator that the state handlers
 * read and mutate as they walk the input character by character.
 *
 * A fresh instance is created for every parse() call and is never stored on the
 * Parse instance, so the parser stays reentrant: a caller-supplied
 * localPartNormalizer closure may call back into parse() mid-parse without
 * clobbering the outer parse's state.
 *
 * The accumulator property names mirror the historical $emailAddress array keys
 * so they thread through the validation helpers unchanged; the public output
 * array shape is built separately in {@see Parse::addAddress()} and is
 * unaffected by this object.
 *
 * @internal Implementation detail of {@see Parse}. The field shape is not a
 *           stable API and may change between minor versions.
 */
final class ParseContext
{
    // --- Per-parse input snapshot and hoisted config (set once in parse(),
    //     never reset between addresses). ---

    /** @var array<int, string> The input split into characters (see parse()). */
    public array $chars = [];

    /** Number of characters in $chars. */
    public int $len = 0;

    /** Whether multiple addresses are being parsed. */
    public bool $multiple = true;

    /** The original input string, retained for diagnostic logging. */
    public string $emails = '';

    /** @var array<string, bool> Separator characters, as a lookup map. */
    public array $separators = [];

    /** @var array<string, bool> Banned characters, as a lookup map. */
    public array $bannedChars = [];

    /** Whether whitespace acts as an address separator. */
    public bool $useWhitespaceAsSeparator = false;

    /** @var array<string, bool> Insignificant (foldable/trimmable) whitespace, as a lookup map. */
    public array $allowedWhitespace = [];

    // --- Loop control state (state/subState reset per address by parse()). ---

    /** Current parser state (one of Parse::STATE_*). */
    public int $state = 0;

    /**
     * Current parser sub-state within an addr-spec (one of Parse::STATE_*).
     * Initialized by the constructor / resetAddress(); the literal 0 default is
     * STATE_TRIM, not a valid starting sub-state (which is STATE_START), so it
     * must never be relied on un-initialized.
     */
    public int $subState = 0;

    /** Current comment nesting depth. */
    public int $commentNestLevel = 0;

    // --- Accumulator fields (reset per address via resetAddress()). ---

    /** Raw address as given, comments included. */
    public string $original_address = '';

    /** Display name without quotes. */
    public string $name_parsed = '';

    /** Local-part without quotes. */
    public string $local_part_parsed = '';

    /** Domain after '@' (may be Unicode/U-label). */
    public string $domain = '';

    /** Punycode A-label domain, populated when it differs from $domain. */
    public ?string $domain_ascii = null;

    /** IP address if a domain-literal was used. */
    public string $ip = '';

    public bool $invalid = false;

    public ?string $invalid_reason = null;

    public ?ParseErrorCode $invalid_reason_code = null;

    public bool $local_part_quoted = false;

    public bool $name_quoted = false;

    public bool $address_temp_quoted = false;

    /**
     * True for exactly the character after a closing quote, so atext / a second
     * quote directly abutting a quoted-string can be rejected.
     */
    public bool $after_closing_quote = false;

    public string $quote_temp = '';

    public string $address_temp = '';

    public int $address_temp_period = 0;

    public ?string $special_char_in_substate = null;

    public string $comment_temp = '';

    /**
     * True for the character following an unescaped backslash inside a comment
     * (RFC 5322 §3.2.1 quoted-pair: "\)" and "\(" are literal, not structural).
     */
    public bool $comment_escaped = false;

    /**
     * True just after a comment closes mid-atom in the local part (atext already
     * accumulated), so the very next character can be inspected.
     */
    public bool $comment_after_local_atext = false;

    /**
     * Set when atext resumes the atom after such a comment. Whether that is an
     * error depends on what the token turns out to be: the local part of an
     * addr-spec (resolved at '@' → reject, RFC 5322 §3.2.3) or a display-name
     * phrase where "word CFWS word" is legal (resolved at '<' → clear).
     */
    public bool $local_atom_split_by_comment = false;

    /** @var array<int, string> Extracted RFC 5322 comments. */
    public array $comments = [];

    /**
     * True while the parser is inside angle-addr (between `<` and `>`).
     * Used to gate obs-route detection per RFC 5322 §4.4.
     */
    public bool $in_angle_addr = false;

    /**
     * Accumulates the obs-route prefix (everything between `<` and the
     * terminating `:`) when ParseOptions::$allowObsRoute is true.
     * Empty string when no obs-route was seen.
     */
    public string $obs_route = '';

    /**
     * @param int $state    Initial parser state (a Parse::STATE_* value).
     * @param int $subState Initial addr-spec sub-state (a Parse::STATE_* value).
     */
    public function __construct(int $state, int $subState)
    {
        // Requiring the initial states makes an un-initialized context
        // unrepresentable: every instance is reset before its first use, so no
        // caller can start parsing from the misleading zero-value field defaults.
        $this->resetAddress($state, $subState);
    }

    /**
     * Resets every accumulator field to its initial value, reusing the instance
     * for the next address in a multi-address parse (matches the historical
     * "rebuild the $emailAddress array" behaviour).
     *
     * @param int $state    Parser state to start the next address in (Parse::STATE_*).
     * @param int $subState Addr-spec sub-state to start it in (Parse::STATE_*).
     */
    public function resetAddress(int $state, int $subState): void
    {
        // Loop-control state, reset here so every per-address field has a single
        // source of truth. commentNestLevel in particular has no other reset:
        // leaving it out would let an unterminated comment leak into the next
        // address in a batch, self-healing only because '(' reassigns it to 1.
        $this->state = $state;
        $this->subState = $subState;
        $this->commentNestLevel = 0;

        $this->original_address = '';
        $this->name_parsed = '';
        $this->local_part_parsed = '';
        $this->domain = '';
        $this->domain_ascii = null;
        $this->ip = '';
        $this->invalid = false;
        $this->invalid_reason = null;
        $this->invalid_reason_code = null;
        $this->local_part_quoted = false;
        $this->name_quoted = false;
        $this->address_temp_quoted = false;
        $this->after_closing_quote = false;
        $this->quote_temp = '';
        $this->address_temp = '';
        $this->address_temp_period = 0;
        $this->special_char_in_substate = null;
        $this->comment_temp = '';
        $this->comment_escaped = false;
        $this->comment_after_local_atext = false;
        $this->local_atom_split_by_comment = false;
        $this->comments = [];
        $this->in_angle_addr = false;
        $this->obs_route = '';
    }
}
