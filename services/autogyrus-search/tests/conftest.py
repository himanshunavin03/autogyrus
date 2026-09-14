import pytest

from autogyrus_search.parser import IntentParser, Lexicon


@pytest.fixture(scope="session")
def parser():
    return IntentParser(Lexicon(
        makes=("Toyota", "Honda", "Ford", "Subaru", "Mazda"),
        models=("RAV4", "Civic", "F-150", "Outback", "CX-5"),
        trims=("XLE", "Sport"), cities=("Edmonton", "Calgary"),
        features=("Heated driver and front passenger seats", "Blind spot monitoring"),
    ))
