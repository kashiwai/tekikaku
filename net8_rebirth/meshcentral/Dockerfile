# MeshCentral Docker Image for Railway
FROM node:20-alpine

# Install dependencies
RUN apk add --no-cache bash tzdata wget

# Set timezone
ENV TZ=Asia/Tokyo

# Create app directory
WORKDIR /opt/meshcentral

# Create directories
RUN mkdir -p meshcentral-data meshcentral-files

# Copy package files
COPY package.json ./

# Install dependencies
RUN npm install --production

# Copy application files
COPY start.js ./
COPY config.json ./meshcentral-data/config.json

# Set environment
ENV NODE_ENV=production

# Expose port (Railway uses PORT env variable)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
  CMD wget --no-verbose --tries=1 --spider http://localhost:${PORT:-8080}/ || exit 1

# Start MeshCentral via custom starter
CMD ["node", "start.js"]
