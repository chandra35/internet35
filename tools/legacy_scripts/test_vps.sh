#!/bin/bash
echo "=== Existing VPs ==="
curl -s http://localhost:7557/virtual-parameters

echo ""
echo ""
echo "=== Trying PUT getTxPower (simple test) ==="
RESULT=$(curl -s -o /dev/null -w "%{http_code}" -X PUT \
  "http://localhost:7557/virtual-parameters/getTxPower" \
  -H "Content-Type: application/json" \
  -d '{"script":"return [Date.now(), \"N/A\"];"}')
echo "HTTP status: $RESULT"

echo ""
echo "=== Trying PUT getWanStatus ==="
RESULT2=$(curl -s -o /dev/null -w "%{http_code}" -X PUT \
  "http://localhost:7557/virtual-parameters/getWanStatus" \
  -H "Content-Type: application/json" \
  -d '{"script":"return [Date.now(), \"Unknown\"];"}')
echo "HTTP status: $RESULT2"

echo ""
echo "=== VPs after PUT ==="
curl -s http://localhost:7557/virtual-parameters
