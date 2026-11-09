#!/usr/bin/env bash

validate_phpunit_output() {
  local output_file="$1"
  local baseline_file="$2"

  if grep -Eq '^(PHP )?Warning:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Warning:"
    grep -E '^(PHP )?Warning:' "${output_file}" || true
    return 1
  fi
  if grep -Eq '^(PHP )?Deprecated:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Deprecated:"
    grep -E '^(PHP )?Deprecated:' "${output_file}" || true
    return 1
  fi
  if grep -Eq '^(PHP )?Notice:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Notice:"
    grep -E '^(PHP )?Notice:' "${output_file}" || true
    return 1
  fi
  if grep -Eq '^OK, but incomplete, skipped, or risky tests!' "${output_file}"; then
    printf '%s\n' "PHPUnit reported incomplete, skipped, or risky tests."
    return 1
  fi
  if grep -Eq '^Skipped:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Skipped:"
    return 1
  fi
  if grep -Eq '^Incomplete:' "${output_file}"; then
    printf '%s\n' "PHPUnit output contains Incomplete:"
    return 1
  fi

  local expected
  expected="$(tr -d '\r' < "${baseline_file}")"
  if ! grep -qF "${expected}" "${output_file}"; then
    printf '%s\n' "PHPUnit output is missing baseline success line: ${expected}"
    return 1
  fi

  printf '%s\n' "${expected}"
  return 0
}
