import type { NextConfig } from "next";

const nextConfig: NextConfig = {
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
