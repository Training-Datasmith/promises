#!/usr/bin/env bash

strip_ansi_file() {
  local input="$1"
  local output="$2"
  sed 's/\x1b\[[0-9;]*[A-Za-z]//g' "${input}" | tr -d '\r' > "${output}"
}

fail_on_php_noise() {
  local stripped="$1"
  local label="$2"
  if grep -Eq '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}"; then
    printf '%s\n' "${label} contains Warning, Deprecated, or Notice."
    grep -En '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" || true
    return 1
  fi
  return 0
}

require_matching_line() {
  local stripped="$1"
  local pattern="$2"
  local label="$3"
  if ! grep -qE "${pattern}" "${stripped}"; then
    printf '%s\n' "${label} is missing required full-line match: ${pattern}"
    return 1
  fi
  return 0
}

require_exact_stdout() {
  local stripped="$1"
  local expected_line="$2"
  local label="$3"
  local line_count
  line_count="$(grep -cve '^[[:space:]]*$' "${stripped}" || true)"
  if [[ "${line_count}" -ne 1 ]]; then
    printf '%s\n' "${label} stdout must contain exactly one non-empty line."
    printf 'Got %s lines:\n' "${line_count}"
    cat "${stripped}"
    return 1
  fi
  local line
  line="$(grep -v '^[[:space:]]*$' "${stripped}" | head -n 1)"
  if [[ "${line}" != "${expected_line}" ]]; then
    printf '%s\n' "${label} stdout is not exactly the required line."
    printf 'Expected: %s\n' "${expected_line}"
    printf 'Got: %s\n' "${line}"
    return 1
  fi
  return 0
}

validate_composer_update_output() {
  local raw_file="$1"
  local stripped
  stripped="$(mktemp)"
  strip_ansi_file "${raw_file}" "${stripped}"
  fail_on_php_noise "${stripped}" "composer update output" || return 1
  require_matching_line "${stripped}" '^No security vulnerability advisories found\.$' "composer update output"
}

validate_composer_audit_output() {
  local raw_file="$1"
  local stripped
  stripped="$(mktemp)"
  strip_ansi_file "${raw_file}" "${stripped}"
  fail_on_php_noise "${stripped}" "composer audit output" || return 1
  require_exact_stdout "${stripped}" 'No security vulnerability advisories found.' "composer audit"
}

validate_check_cluster_pins_output() {
  local raw_file="$1"
  local stripped
  stripped="$(mktemp)"
  strip_ansi_file "${raw_file}" "${stripped}"
  fail_on_php_noise "${stripped}" "check-cluster-pins output" || return 1
  require_exact_stdout "${stripped}" 'check-cluster-pins: all cluster packages match expected SHAs and aliases.' "check-cluster-pins"
}

validate_check_rename_complete_output() {
  local raw_file="$1"
  local stripped
  stripped="$(mktemp)"
  strip_ansi_file "${raw_file}" "${stripped}"
  fail_on_php_noise "${stripped}" "check-rename-complete output" || return 1
  require_exact_stdout "${stripped}" 'check-rename-complete: no old repo-owned names remain.' "check-rename-complete"
}
