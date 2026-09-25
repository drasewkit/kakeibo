FROM node:22-alpine

# ベースイメージ同梱のnpmは内部依存（tar/brace-expansion等）に脆弱性があるため最新版に更新する
RUN npm install -g npm@latest

WORKDIR /app

COPY entrypoint.sh ./
RUN chmod +x entrypoint.sh

EXPOSE 3000

ENTRYPOINT ["./entrypoint.sh"]
