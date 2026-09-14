import json
from pathlib import Path

import pytest

from autogyrus_search.models import QueryRequest

CASES = [json.loads(line) for line in Path(__file__).with_name("evaluation_queries.jsonl").read_text(encoding="utf-8").splitlines()]


@pytest.mark.parametrize("case", CASES, ids=[item["query"] for item in CASES])
def test_evaluation_expected_fields(parser, case):
    intent = parser.parse(QueryRequest(query=case["query"]))
    actual = intent.model_dump()
    for section, expected in case["expected"].items():
        for key, value in expected.items():
            if isinstance(value, list):
                assert set(value) <= set(actual[section][key])
            else:
                assert actual[section][key] == value


def test_evaluation_has_at_least_100_distinct_queries():
    assert len(CASES) >= 100
    assert len({case["query"] for case in CASES}) == len(CASES)
