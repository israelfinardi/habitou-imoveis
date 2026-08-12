import type { Metadata } from "next";

export const metadata: Metadata = { title: "Termos de uso" };

const SECTIONS = [
  "Links Eletrônicos e Referências ao Habitou Imóveis",
  "Facilidades Oferecidas pelo Habitou Imóveis",
  "Responsabilidades do Habitou Imóveis",
  "Conteúdo do Habitou Imóveis",
  "Conteúdo Incluído por Você no Habitou Imóveis",
  "Direitos Autorais, Propriedade Intelectual e Marcas",
  "Política de Privacidade",
];

export default function TermosDeUsoPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 className="mb-2 text-3xl font-bold text-brand-text">Termos de uso</h1>
      <p className="mb-8 text-sm text-brand-text-secondary">Última verificação de conteúdo: 2026</p>

      <div className="space-y-4 text-sm leading-relaxed text-brand-text">
        <p>
          O presente termo regula as condições gerais de utilização do website &ldquo;Habitou Imóveis&rdquo;
          (www.habitou.com.br), plataforma destinada a corretores e imobiliárias registrados junto ao CRECI-SC para
          divulgação de imóveis em Santa Catarina.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Definições</h2>
        <p>Para os fins destes Termos de Uso, as seguintes definições serão adotadas:</p>
        <p><strong>Termos de uso:</strong> são os termos contidos nesta página.</p>
        <p><strong>Habitou Imóveis:</strong> website com endereço http://www.habitou.com.br.</p>
        <p><strong>Você:</strong> é qualquer pessoa que acessa ou utiliza o Habitou Imóveis.</p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Leitura e Aceitação</h2>
        <p>
          Antes de acessar ou utilizar qualquer parte do Habitou Imóveis, VOCÊ deverá ler com atenção estes Termos.
          O uso do Habitou Imóveis, ou acesso a este, implicará que VOCÊ leu, concordou e aceitou cumprir com os
          termos, condições e advertências aqui estabelecidos. Estes Termos de Uso podem ser modificados pelo
          Habitou Imóveis a qualquer momento e sem a necessidade de aviso prévio, estando VOCÊ ciente de que será
          de sua exclusiva responsabilidade verificá-los periodicamente.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">1. Limitações e Uso Adequado</h2>
        <p>
          O acesso e a utilização do Habitou Imóveis são oferecidos a VOCÊ unicamente para o seu uso pessoal e não
          comercial. Entre outros fatores, VOCÊ concorda que não poderá: modificar, copiar, distribuir, transmitir,
          exibir, reproduzir, publicar, licenciar, criar trabalhos derivados ou vender qualquer informação, software
          ou banco de dados obtidos pelo Habitou Imóveis, incluindo práticas de &ldquo;screen scraping&rdquo; ou
          &ldquo;database scraping&rdquo;; enviar informações falsas, enganosas, ofensivas ou que violem a lei;
          efetuar cadastro utilizando informações falsas ou de terceiros; transmitir códigos maliciosos ou vírus;
          acessar áreas restritas sem autorização; ou realizar qualquer atividade que viole direitos de propriedade
          intelectual ou prejudique o funcionamento da plataforma.
        </p>

        {SECTIONS.map((title, i) => (
          <div key={title}>
            <h2 className="pt-4 text-lg font-bold text-brand-text">{i + 2}. {title}</h2>
          </div>
        ))}

        <h2 className="pt-4 text-lg font-bold text-brand-text">Termos Gerais</h2>
        <p>
          O Habitou Imóveis, de tempos em tempos, poderá modificar estes Termos de Uso, sem aviso prévio. Estas
          modificações serão incorporadas aos Termos de Uso imediatamente, a partir de sua publicação. VOCÊ entende
          e aceita que deverá rever estes Termos de Uso periodicamente.
        </p>

        <p className="pt-6 text-xs text-brand-text-secondary">
          Dúvidas sobre estes termos podem ser enviadas através da nossa{" "}
          <a href="/fale-conosco" className="text-brand-primary hover:underline">página de contato</a>.
        </p>
      </div>
    </div>
  );
}
