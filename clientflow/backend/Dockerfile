FROM node:20-alpine
RUN apk add --no-cache openssl
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY prisma ./prisma/
RUN npx prisma generate --schema=prisma/schema.production.prisma
COPY . .
ENV NODE_ENV=production
EXPOSE 3000
CMD ["sh", "-c", "npx prisma db push --schema=prisma/schema.production.prisma --skip-generate && node src/index.js"]
