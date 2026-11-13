# CRM F3-59 — strict byte-canonical metadata-only wire JSON

A raw JSON object can contain duplicate keys or Unicode-escaped aliases that collapse during ordinary `json_decode(..., true)`. This may make a received, signed value ambiguous across language runtimes. The Core candidate now **refuses every body** that differs byte-for-byte from the documented eight-key, compact JSON serialization before HMAC and server binding checks.

Tests include duplicate `protocol`, Unicode escaped key, reordered keys, superfluous whitespace/newline, injected contact data, numeric version alternative, oversized body, malformed JSON and non-object JSON. These are **technical metadata-only envelopes**, not general-purpose JSON input or customer record validation.

The F3-56 CI-only HTTP router uses this Core decoder before delivery/readback and keeps all operational claims false. This is not a publicly available parser or a release decision. Identity, key rotation, real PostgreSQL lead persistence, app-level authorization, AppSec/DBA review and VPS homologation remain outstanding.
