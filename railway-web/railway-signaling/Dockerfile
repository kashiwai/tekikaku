FROM node:18-alpine
WORKDIR /app
COPY package.json ./
COPY app.json ./
RUN npm install --production
COPY bin ./bin
COPY lib ./lib
EXPOSE 8080
CMD ["node", "bin/peerjs", "--port", "8080"]
