#!/usr/bin/env python3
"""Hermes hook: Telegram resolve must use the numeric user id, never the display name."""

from __future__ import annotations

import json
import os
import re
import sys

SESSION_KEY_ID = re.compile(r'telegram:dm:(\d+)')


def digits(value: object) -> str:
    text = str(value or '').strip()
    return text if text.isdigit() else ''


def telegram_id(payload: dict) -> str:
    extra = payload.get('extra') if isinstance(payload.get('extra'), dict) else {}

    for candidate in (
        os.environ.get('HERMES_SESSION_USER_ID'),
        extra.get('sender_id'),
        extra.get('user_id'),
        payload.get('sender_id'),
    ):
        found = digits(candidate)
        if found:
            return found

    for candidate in (
        extra.get('session_key'),
        payload.get('session_id'),
        extra.get('session_id'),
    ):
        match = SESSION_KEY_ID.search(str(candidate or ''))
        if match:
            return match.group(1)

    return ''


def main() -> None:
    try:
        payload = json.load(sys.stdin)
    except json.JSONDecodeError:
        print('{}')
        return

    if not isinstance(payload, dict):
        print('{}')
        return

    extra = payload.get('extra') if isinstance(payload.get('extra'), dict) else {}
    platform = str(extra.get('platform') or payload.get('platform') or os.environ.get('HERMES_SESSION_PLATFORM') or '')
    event = str(payload.get('hook_event_name') or '')
    identifier = telegram_id(payload)

    if platform != 'telegram' or identifier == '':
        print('{}')
        return

    if event == 'pre_tool_call':
        tool = str(payload.get('tool_name') or '')
        args = payload.get('tool_input')
        if 'resolve' not in tool or not isinstance(args, dict):
            print('{}')
            return

        current = str(args.get('identifier') or '')
        channel = str(args.get('channel') or '')
        if channel == 'telegram' and not current.isdigit():
            rewritten = dict(args)
            rewritten['identifier'] = identifier
            json.dump({'action': 'modify', 'args': rewritten}, sys.stdout)
            return

        print('{}')
        return

    json.dump({
        'context': (
            f'EnterLMS resolve identifier (digits only): {identifier}\n'
            'Call resolve with channel=telegram and this identifier. Never the display name.'
        ),
    }, sys.stdout)


if __name__ == '__main__':
    main()
