"""Operational indexing commands; suitable for a separately scheduled worker."""
import argparse
import json
import logging

from .config import get_settings
from .service import SearchService


def main() -> None:
    parser = argparse.ArgumentParser(description="Manage the derived AutoGyrus search index")
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("rebuild")
    sub.add_parser("ready")
    one = sub.add_parser("vehicle")
    one.add_argument("vehicle_id", type=int)
    many = sub.add_parser("vehicles")
    many.add_argument("vehicle_ids", type=int, nargs="+")
    args = parser.parse_args()
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    service = SearchService.from_settings(get_settings())
    try:
        if args.command == "rebuild":
            result = service.rebuild()
        elif args.command == "vehicle":
            result = {"vehicle_id": args.vehicle_id, "status": service.index_vehicle(args.vehicle_id)}
        elif args.command == "vehicles":
            result = service.index_vehicles(args.vehicle_ids)
        else:
            service.ready()
            result = {"status": "ready"}
        print(json.dumps(result))
    finally:
        service.close()


if __name__ == "__main__":
    main()
