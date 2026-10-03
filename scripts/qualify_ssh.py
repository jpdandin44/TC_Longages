"""CLI for the generic composite Action; no site adapter or remote mutation."""
import json
import os
import sys
from delivery_shared import qualify_ssh, cleanup_ssh

if __name__ == '__main__':
    if sys.argv[1:] == ['cleanup']:
        cleanup_ssh(os.environ)
    else:
        print(json.dumps(qualify_ssh(os.environ), indent=2))
