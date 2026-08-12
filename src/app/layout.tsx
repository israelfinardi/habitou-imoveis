import type { Metadata } from "next";
import { Public_Sans, Sora } from "next/font/google";
import "./globals.css";
import { SiteHeader } from "@/components/layout/SiteHeader";
import { SiteFooter } from "@/components/layout/SiteFooter";
import { getCurrentUser } from "@/lib/auth/session";

const publicSans = Public_Sans({
  variable: "--font-public-sans",
  subsets: ["latin"],
});

const sora = Sora({
  variable: "--font-sora",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: {
    default: "Habitou Imóveis — Apartamentos, casas e terrenos em Santa Catarina",
    template: "%s | Habitou Imóveis",
  },
  description:
    "Encontre apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina. Anuncie seu imóvel ou encontre imobiliárias e corretores de confiança.",
  metadataBase: new URL(process.env.APP_URL || "http://localhost:3000"),
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();

  return (
    <html lang="pt-BR" className={`${publicSans.variable} ${sora.variable} h-full antialiased`}>
      <body className="min-h-full flex flex-col bg-background text-foreground">
        <SiteHeader user={user} />
        <main className="flex-1">{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}
