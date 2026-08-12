import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "assets.imoveis-sc.com.br",
      },
    ],
  },
};

export default nextConfig;
