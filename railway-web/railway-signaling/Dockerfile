FROM node:18-alpine
WORKDIR /app
COPY package.json .
COPY bin ./bin
COPY lib ./lib
COPY app.json .
RUN npm install --production
EXPOSE 8080
CMD ["node", "bin/peerjs", "--port", "8080"]
