#!/bin/sh
set -e

# コンテナ起動のたびにインストールし直すことで、ホスト側のnode_modulesとも常に同期させる
# （node_modulesはbind mountでホストと共有しているため、ここでの結果がそのままホストにも反映される）
npm install

npm run dev
