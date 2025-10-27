FROM node:18-alpine
WORKDIR /app

# Cache bust: 2025-01-27
ARG CACHEBUST=1

COPY package.json ./
COPY app.json ./
RUN npm install --production
COPY bin ./bin
COPY lib ./lib
EXPOSE 8080
CMD ["node", "bin/peerjs", "--port", "8080"]
