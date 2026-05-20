#!/bin/bash
for id in 69ed871312f531527ea3bfde 6934e888c5cb2c50efbb3ee1 6934e8945895e6e287a89ffb 69b36850c9ad6847ab4cb269 69b3ee4bfd9613229230f784 69b3ee5b6e29bb6d84d889c9 69b3ee63fd9613229230f785; do
  echo -n "DELETE $id: "
  curl -s -o /dev/null -w '%{http_code}' -X DELETE "http://172.10.10.254:7557/tasks/$id"
  echo
done
