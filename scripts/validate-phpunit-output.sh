#!/usr/bin/env bash

strip_ansi_file() {
  local input="$1"
  local output="$2"
  sed 's/\x1b\[[0-9;]*[A-Za-z]//g' "${input}" | tr -d '\r' > "${output}"
}

validate_phpunit_output() {
  local output_file="$1"
  local baseline_file="$2"
  local stripped
  stripped="$(mktemp)"
  strip_ansi_file "${output_file}" "${stripped}"

  if grep -Eq '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}"; then
    printf '%s\n' "PHPUnit output contains Warning, Deprecated, or Notice."
    grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" || true
    return 1
  fi
  if grep -Eq '^OK, but incomplete, skipped, or risky tests!' "${stripped}"; then
    printf '%s\n' "PHPUnit reported incomplete, skipped, or risky tests."
    return 1
  fi
  if grep -Eq '^Skipped:' "${stripped}"; then
    printf '%s\n' "PHPUnit output contains Skipped:"
    return 1
  fi
  if grep -Eq '^Incomplete:' "${stripped}"; then
    printf '%s\n' "PHPUnit output contains Incomplete:"
    return 1
  fi

  local expected
  expected="$(tr -d '\r' < "${baseline_file}")"
  if ! grep -qE '^OK \([0-9]+ tests, [0-9]+ assertions\)$' "${stripped}"; then
    printf '%s\n' 'PHPUnit output is missing strict success line: OK (N tests, M assertions)'
    return 1
  fi
  if ! grep -qFx "${expected}" "${stripped}"; then
    printf '%s\n' "PHPUnit output is missing baseline success line: ${expected}"
    return 1
  fi

  printf '%s\n' "${expected}"
  return 0
}
