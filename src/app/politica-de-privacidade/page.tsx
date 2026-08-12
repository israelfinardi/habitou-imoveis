import type { Metadata } from "next";

export const metadata: Metadata = { title: "Política de privacidade" };

export default function PoliticaDePrivacidadePage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 className="mb-8 text-3xl font-bold text-brand-text">Política de privacidade</h1>

      <div className="space-y-4 text-sm leading-relaxed text-brand-text">
        <p>
          O presente documento estabelece a política de privacidade referente às condições gerais de utilização do
          website &ldquo;Habitou Imóveis&rdquo; (www.habitou.com.br).
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Definições</h2>
        <p><strong>Anunciantes:</strong> são todas as pessoas que se cadastram no Habitou Imóveis e que concluem o cadastro de anúncio de um imóvel.</p>
        <p><strong>Usuários:</strong> são todas as pessoas que acessam o Habitou Imóveis, independentemente de se cadastrarem ou não.</p>
        <p><strong>Você:</strong> são os Usuários ou Anunciantes, dependendo do contexto em que esta definição é utilizada.</p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Qual informação é coletada de mim?</h2>
        <p>
          O Habitou Imóveis coleta informações pessoais fornecidas por VOCÊ, informações estas capazes de
          identificá-lo. É possível visitar a maioria das seções do Habitou Imóveis sem a necessidade de enviar
          nenhuma informação pessoal identificável. Entretanto, para acesso a determinados conteúdos e recursos
          (como cadastro, favoritos e publicação de anúncios), poderemos solicitar informações como nome completo,
          e-mail, telefone e, para anunciantes, dados profissionais (CRECI, CNPJ da imobiliária).
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Coleta automática de informações</h2>
        <p>
          O Habitou Imóveis rastreia determinadas informações sobre VOCÊ à medida em que VOCÊ visita e utiliza
          nossos serviços, com o único propósito de entender melhor como a plataforma é utilizada e como nossos
          serviços podem ser aprimorados. Estas informações podem incluir endereço IP, páginas visitadas e tipo de
          navegador, coletadas através de cookies.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Cookies</h2>
        <p>
          &ldquo;Cookies&rdquo; são pequenos arquivos eletrônicos armazenados no seu navegador para que possamos
          reconhecê-lo na próxima visita. VOCÊ está livre para recusar cookies, se seu navegador assim permitir.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Como as minhas informações são protegidas?</h2>
        <p>
          Utilizamos senhas com hash criptográfico (nunca armazenadas em texto puro), conexões seguras (HTTPS) e
          controle de acesso por papel de usuário. Embora o Habitou Imóveis tome precauções com a segurança das
          suas informações, nenhum sistema é 100% imune a incidentes de segurança.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Compartilhamento de informações</h2>
        <p>
          Não vendemos suas informações pessoais a terceiros. Dados de contato podem ser compartilhados com o
          anunciante/imobiliária responsável pelo imóvel quando VOCÊ envia uma mensagem de interesse.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Seus direitos (LGPD)</h2>
        <p>
          Você pode solicitar a qualquer momento a confirmação, correção, portabilidade ou eliminação dos seus
          dados pessoais, através da nossa <a href="/fale-conosco" className="text-brand-primary hover:underline">página de contato</a>.
        </p>

        <h2 className="pt-4 text-lg font-bold text-brand-text">Data efetiva e modificações</h2>
        <p>
          Esta Política de Privacidade pode ser atualizada periodicamente. Recomendamos que VOCÊ a revise com
          regularidade.
        </p>
      </div>
    </div>
  );
}
