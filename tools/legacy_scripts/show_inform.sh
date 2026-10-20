#!/bin/bash
mongo genieacs --quiet --eval 'print(db.provisions.findOne({_id:"inform"}).script)'
